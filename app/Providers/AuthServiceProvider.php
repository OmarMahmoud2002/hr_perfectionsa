<?php

namespace App\Providers;

use App\Models\DailyPerformanceEntry;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeMonthTask;
use App\Models\LeaveRequest;
use App\Policies\DailyPerformanceReviewPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeeVisibilityPolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\TaskAssignmentPolicy;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Department::class => DepartmentPolicy::class,
        LeaveRequest::class => LeaveRequestPolicy::class,
        Employee::class => EmployeeVisibilityPolicy::class,
        DailyPerformanceEntry::class => DailyPerformanceReviewPolicy::class,
        EmployeeMonthTask::class => TaskAssignmentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
