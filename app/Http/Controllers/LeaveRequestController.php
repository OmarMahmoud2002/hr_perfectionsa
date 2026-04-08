<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveEligibilityService;
use App\Services\Leave\LeaveRequestException;
use App\Services\Leave\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveEligibilityService $eligibilityService,
        private readonly LeaveBalanceService $balanceService,
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user()->loadMissing('employee.department.managerEmployee.user');
        $employee = $user->employee;

        if ($employee === null) {
            return back()->with('error', 'حسابك غير مرتبط بملف موظف.');
        }

        $cycle = $this->balanceService->resolveCycleForDate($employee, now());
        $eligibility = $this->eligibilityService->evaluate($employee, now());
        $balance = $this->balanceService->ensureYearBalance($employee, (int) $cycle['cycle_year']);

        $requests = LeaveRequest::query()
            ->where('employee_id', (int) $employee->id)
            ->with([
                'approvals' => function ($query) {
                    $query->latest('decided_at')->latest('id')->with('actor:id,name');
                },
                'managerEmployee.user:id,name',
            ])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('leave.employee', [
            'employee' => $employee,
            'eligibility' => $eligibility,
            'balance' => $balance,
            'leaveRequests' => $requests,
            'today' => now()->toDateString(),
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $employee = $request->user()->loadMissing('employee')->employee;

        if ($employee === null) {
            return back()->with('error', 'حسابك غير مرتبط بملف موظف.');
        }

        try {
            $this->leaveRequestService->submit(
                $employee,
                Carbon::parse((string) $request->string('start_date')),
                Carbon::parse((string) $request->string('end_date')),
                $request->filled('reason') ? (string) $request->string('reason') : null,
                now(),
            );
        } catch (LeaveRequestException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم إرسال طلب الإجازة بنجاح.');
    }
}
