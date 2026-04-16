<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\JobTitle;
use App\Models\Location;
use App\Services\Attendance\AbsenceDetectionService;
use App\Services\Attendance\PublicHolidayService;
use App\Services\Employee\EmployeeService;
use App\Services\Payroll\PayrollPeriod;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService,
        private readonly AbsenceDetectionService $absenceService,
        private readonly PublicHolidayService $holidayService,
    ) {}

    /**
     * عرض قائمة الموظفين
     */
    public function index(Request $request): View
    {
        $actor = $request->user();

        if (in_array((string) $actor?->role, ['employee', 'office_girl'], true)) {
            return $this->allCards($request);
        }

        $filters = $request->only(['search']);
        $employees = $this->employeeService->getEmployees(
            $filters,
            perPage: 15,
            actor: $actor,
        );

        return view('employees.index', [
            'employees' => $employees,
            'filters' => $filters,
        ]);
    }

    public function allCards(Request $request): View
    {
        $actor = $request->user();

        abort_unless(
            in_array((string) $actor?->role, ['admin', 'manager', 'hr', 'department_manager', 'employee', 'office_girl', 'user'], true),
            403
        );

        $search = trim((string) $request->query('search', ''));

        $query = Employee::query()
            ->with(['user.profile', 'department:id,name', 'jobTitleRef:id,name_ar', 'leaveProfile:employee_id,employment_start_date'])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('ac_no', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(18)->withQueryString();

        return view('employees.all-cards', [
            'employees' => $employees,
            'search' => $search,
        ]);
    }

    /**
     * عرض نموذج إضافة موظف
     */
    public function create(): View
    {
        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude', 'radius']);

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $jobTitles = JobTitle::query()
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'key', 'system_role_mapping']);

        return view('employees.create', compact('locations', 'departments', 'jobTitles'));
    }

    /**
     * حفظ موظف جديد
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->employeeService->create($request->validated());
        $locationIds = $request->boolean('is_remote_worker')
            ? $request->input('location_ids', [])
            : [];
        $remoteAllowedDates = $request->boolean('is_remote_worker')
            ? array_values(array_unique((array) $request->input('remote_allowed_dates', [])))
            : [];

        $employee->locations()->sync($locationIds);
        $employee->remoteWorkDays()->delete();
        if (! empty($remoteAllowedDates)) {
            $employee->remoteWorkDays()->createMany(
                collect($remoteAllowedDates)->map(fn (string $date) => ['work_date' => $date])->all()
            );
        }
        $account = $employee->user;

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "تم إضافة الموظف «{$employee->name}» بنجاح.")
            ->with('info', "بيانات الدخول: {$account->email} | كلمة السر الأولية: 123456789");
    }

    /**
     * عرض تفاصيل موظف
     */
    public function show(Employee $employee, Request $request): View
    {
        if ($request->user()?->isViewer()) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الصفحة.');
        }

        $employee->loadMissing('user.profile', 'department', 'jobTitleRef');

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        [$periodStartDate, $periodEndDate] = PayrollPeriod::resolve($month, $year);
        $periodStart = $periodStartDate->toDateString();
        $periodEnd   = $periodEndDate->toDateString();

        // جلب batch والإجازات الرسمية
        $batch = ImportBatch::where('month', $month)
            ->where('year', $year)
            ->where('status', ImportStatus::Completed)
            ->first();

        $publicHolidays = $batch ? $this->holidayService->getHolidayDates($batch) : [];

        // التفاصيل اليومية مع تطبيق قاعدة الإجازة الأسبوعية
        $dailyBreakdown = $this->absenceService->getDailyBreakdown($employee, $month, $year, $publicHolidays);

        // الإحصائيات الصحيحة
        $monthlyStats = $this->absenceService->getMonthlyStats($employee, $month, $year, $publicHolidays);
        $stats = [
            'present'      => $monthlyStats['total_present_days'],
            'absent'       => $monthlyStats['total_absent_days'],
            'late'         => $monthlyStats['total_late_minutes'],
            'overtime'     => $monthlyStats['total_overtime_minutes'],
            'weekly_leave' => $monthlyStats['total_weekly_leave_days'],
        ];

        // أحدث راتب
        $payroll = $employee->payrollReports()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return view('employees.show', compact('employee', 'stats', 'payroll', 'month', 'year', 'periodStart', 'periodEnd', 'dailyBreakdown'));
    }

    /**
     * عرض نموذج تعديل موظف
     */
    public function edit(Employee $employee): View
    {
        $employee->loadMissing('user', 'locations', 'department', 'jobTitleRef', 'remoteWorkDays');

        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude', 'radius']);

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $jobTitles = JobTitle::query()
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'key', 'system_role_mapping']);

        $selectedLocationIds = old('location_ids', $employee->locations->pluck('id')->all());
        $selectedRemoteDates = old(
            'remote_allowed_dates',
            $employee->remoteWorkDays->pluck('work_date')->map(fn ($date) => (string) $date->toDateString())->all()
        );

        return view('employees.edit', compact('employee', 'locations', 'selectedLocationIds', 'selectedRemoteDates', 'departments', 'jobTitles'));
    }

    /**
     * تحديث بيانات موظف
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee = $this->employeeService->update($employee, $request->validated());
        $locationIds = $request->boolean('is_remote_worker')
            ? $request->input('location_ids', [])
            : [];
        $remoteAllowedDates = $request->boolean('is_remote_worker')
            ? array_values(array_unique((array) $request->input('remote_allowed_dates', [])))
            : [];

        $employee->locations()->sync($locationIds);
        $employee->remoteWorkDays()->delete();
        if (! empty($remoteAllowedDates)) {
            $employee->remoteWorkDays()->createMany(
                collect($remoteAllowedDates)->map(fn (string $date) => ['work_date' => $date])->all()
            );
        }
        $account = $employee->user;

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', "تم تحديث بيانات الموظف «{$employee->name}» بنجاح.")
            ->with('info', "البريد المرتبط بالحساب: {$account->email}");
    }

    /**
     * حذف نهائي للموظف
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $name = $employee->name;
        $this->employeeService->deletePermanently($employee);

        return redirect()
            ->route('employees.index')
            ->with('success', "تم حذف الموظف «{$name}» نهائياً بنجاح.");
    }
}

