<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideLeaveRequestRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveApprovalService;
use App\Services\Leave\LeaveEligibilityService;
use App\Services\Leave\LeaveRequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LeaveApprovalController extends Controller
{
    public function __construct(
        private readonly LeaveApprovalService $approvalService,
        private readonly LeaveEligibilityService $eligibilityService,
    ) {}

    private function ensureHrLikeAccess(Request $request): void
    {
        abort_unless(in_array((string) $request->user()?->role, ['admin', 'manager', 'hr'], true), 403);
    }

    public function index(Request $request): View
    {
        $actor = $request->user();
        $isHrLike = in_array($actor->role, ['admin', 'manager', 'hr'], true);

        $statusFilter = (string) $request->query('status', 'all');
        $monthFilter = (int) $request->query('month', (int) now()->month);
        $yearFilter = (int) $request->query('year', (int) now()->year);

        $query = LeaveRequest::query()
            ->with([
                'employee.user.profile',
                'employee.department',
                'managerEmployee.user',
                'approvals.actor:id,name',
            ])
            ->whereYear('start_date', $yearFilter)
            ->whereMonth('start_date', $monthFilter)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if (! $isHrLike) {
            $query
                ->where('manager_employee_id', (int) ($actor->employee_id ?? 0))
                ->where('manager_status', '!=', 'not_required');
        }

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $leaveRequests = $query->paginate(12)->withQueryString();

        return view('leave.manage', [
            'leaveRequests' => $leaveRequests,
            'statusFilter' => $statusFilter,
            'monthFilter' => $monthFilter,
            'yearFilter' => $yearFilter,
            'isHrLike' => $isHrLike,
            'actorEmployeeId' => (int) ($actor->employee_id ?? 0),
        ]);
    }

    public function employeeSettings(Request $request): View
    {
        $this->ensureHrLikeAccess($request);

        $year = (int) $request->query('year', (int) now()->year);
        $search = trim((string) $request->query('search', ''));
        $departmentId = (int) $request->query('department_id', 0);

        $query = Employee::query()
            ->with([
                'department:id,name,manager_employee_id',
                'department.managerEmployee.user:id,name,employee_id',
                'leaveProfile:employee_id,employment_start_date,required_work_days_before_leave,annual_leave_quota',
                'leaveBalances' => fn ($q) => $q->where('year', $year),
            ])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('ac_no', 'like', "%{$search}%");
            });
        }

        if ($departmentId > 0) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->paginate(15)->withQueryString();

        $employees->getCollection()->transform(function (Employee $employee) use ($year) {
            $eligibility = $this->eligibilityService->evaluate($employee);
            $balance = $employee->leaveBalances->first();
            $annualQuota = (int) ($employee->leaveProfile?->annual_leave_quota ?? $eligibility['annual_leave_quota']);
            $usedDays = (int) ($balance?->used_days ?? 0);
            $remainingDays = (int) ($balance?->remaining_days ?? max(0, $annualQuota - $usedDays));

            $employee->setAttribute('eligibility_snapshot', [
                'days_remaining_to_eligibility' => (int) ($eligibility['days_remaining_to_eligibility'] ?? 0),
                'status' => (bool) ($eligibility['eligible'] ?? false),
                'annual_quota_days' => $annualQuota,
                'used_days' => $usedDays,
                'remaining_days' => $remainingDays,
                'required_work_days' => (int) ($eligibility['required_work_days'] ?? 0),
            ]);

            return $employee;
        });

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaultRequiredDays = $this->eligibilityService->requiredWorkDays();
        $defaultAnnualQuota = $this->eligibilityService->annualLeaveQuota();

        return view('leave.employee-settings', [
            'employees' => $employees,
            'departments' => $departments,
            'search' => $search,
            'departmentId' => $departmentId,
            'year' => $year,
            'defaultRequiredDays' => $defaultRequiredDays,
            'defaultAnnualQuota' => $defaultAnnualQuota,
        ]);
    }

    public function updateEmployeeSetting(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensureHrLikeAccess($request);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'employment_start_date' => ['nullable', 'date'],
            'required_work_days_before_leave' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'annual_leave_quota' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $profile = $employee->leaveProfile()->firstOrNew();
        $profile->employee_id = (int) $employee->id;
        $profile->employment_start_date = $validated['employment_start_date'] ?: null;
        $profile->required_work_days_before_leave = $validated['required_work_days_before_leave'] !== null
            ? (int) $validated['required_work_days_before_leave']
            : null;
        $profile->annual_leave_quota = $validated['annual_leave_quota'] !== null
            ? (int) $validated['annual_leave_quota']
            : null;
        $profile->save();

        $year = (int) $validated['year'];
        $annualQuota = (int) ($profile->annual_leave_quota ?? $this->eligibilityService->annualLeaveQuota());

        $balance = LeaveBalance::query()->firstOrCreate(
            ['employee_id' => (int) $employee->id, 'year' => $year],
            ['annual_quota_days' => $annualQuota, 'used_days' => 0, 'remaining_days' => $annualQuota]
        );

        $balance->annual_quota_days = $annualQuota;
        $balance->remaining_days = max(0, (int) $balance->annual_quota_days - (int) $balance->used_days);
        $balance->save();

        return back()->with('success', 'تم تحديث إعدادات الإجازات للموظف بنجاح.');
    }

    public function bulkUpdateEmployeeSettings(Request $request): RedirectResponse
    {
        $this->ensureHrLikeAccess($request);

        $year = (int) $request->input('year', (int) now()->year);

        $validator = Validator::make($request->all(), [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'rows.*.employment_start_date' => ['nullable', 'date'],
            'rows.*.required_work_days_before_leave' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'rows.*.annual_leave_quota' => ['nullable', 'integer', 'min:0', 'max:365'],
        ], [
            'rows.required' => 'لا توجد بيانات للحفظ.',
            'rows.*.employee_id.exists' => 'أحد الموظفين المحددين غير موجود.',
            'rows.*.employment_start_date.date' => 'تاريخ بداية العمل غير صالح.',
            'rows.*.required_work_days_before_leave.integer' => 'أيام الخدمة يجب أن تكون رقمًا صحيحًا.',
            'rows.*.annual_leave_quota.integer' => 'الرصيد السنوي يجب أن يكون رقمًا صحيحًا.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($validated, $year): void {
            foreach ($validated['rows'] as $row) {
                $employee = Employee::query()->findOrFail((int) $row['employee_id']);

                $profile = $employee->leaveProfile()->firstOrNew();
                $profile->employee_id = (int) $employee->id;
                $profile->employment_start_date = $row['employment_start_date'] ?: null;
                $profile->required_work_days_before_leave = $row['required_work_days_before_leave'] !== null
                    ? (int) $row['required_work_days_before_leave']
                    : null;
                $profile->annual_leave_quota = $row['annual_leave_quota'] !== null
                    ? (int) $row['annual_leave_quota']
                    : null;
                $profile->save();

                $annualQuota = (int) ($profile->annual_leave_quota ?? $this->eligibilityService->annualLeaveQuota());

                $balance = LeaveBalance::query()->firstOrCreate(
                    ['employee_id' => (int) $employee->id, 'year' => $year],
                    ['annual_quota_days' => $annualQuota, 'used_days' => 0, 'remaining_days' => $annualQuota]
                );

                $balance->annual_quota_days = $annualQuota;
                $balance->remaining_days = max(0, (int) $balance->annual_quota_days - (int) $balance->used_days);
                $balance->save();
            }
        });

        return back()->with('success', 'تم حفظ جميع تعديلات إعدادات الموظفين بنجاح.');
    }

    public function applyDefaultEmployeeSettings(Request $request): RedirectResponse
    {
        $this->ensureHrLikeAccess($request);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'exists:employees,id'],
        ]);

        $year = (int) $validated['year'];
        $employeeIds = array_map('intval', $validated['employee_ids']);
        $defaultRequiredDays = $this->eligibilityService->requiredWorkDays();
        $defaultAnnualQuota = $this->eligibilityService->annualLeaveQuota();

        $employees = Employee::query()->whereIn('id', $employeeIds)->get(['id']);

        foreach ($employees as $employee) {
            $profile = $employee->leaveProfile()->firstOrNew();
            $profile->employee_id = (int) $employee->id;
            $profile->required_work_days_before_leave = $defaultRequiredDays;
            $profile->annual_leave_quota = $defaultAnnualQuota;
            $profile->save();

            $balance = LeaveBalance::query()->firstOrCreate(
                ['employee_id' => (int) $employee->id, 'year' => $year],
                ['annual_quota_days' => $defaultAnnualQuota, 'used_days' => 0, 'remaining_days' => $defaultAnnualQuota]
            );

            $balance->annual_quota_days = $defaultAnnualQuota;
            $balance->remaining_days = max(0, (int) $balance->annual_quota_days - (int) $balance->used_days);
            $balance->save();
        }

        return back()->with('success', 'تم تطبيق القيم الافتراضية على الموظفين المحددين.');
    }

    public function decide(DecideLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $approvedDates = $request->input('approved_dates');

            $this->approvalService->decide(
                $leaveRequest,
                $request->user(),
                (string) $request->string('decision'),
                $request->filled('approved_days') ? (int) $request->integer('approved_days') : null,
                $request->filled('note') ? (string) $request->string('note') : null,
                now(),
                is_array($approvedDates) ? $approvedDates : null,
            );
        } catch (LeaveRequestException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم حفظ القرار على طلب الإجازة.');
    }
}
