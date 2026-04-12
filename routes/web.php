<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PublicHolidayController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\MyAccountController;
use App\Http\Controllers\EmployeeOfMonthVoteController;
use App\Http\Controllers\EmployeeOfMonthAdminController;
use App\Http\Controllers\TaskAdminController;
use App\Http\Controllers\TaskEvaluationController;
use App\Http\Controllers\EmployeeMyTasksController;
use App\Http\Controllers\DailyPerformanceEmployeeController;
use App\Http\Controllers\DailyPerformanceReviewController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RemoteAttendanceController;
use App\Http\Controllers\EmployeeRemoteAttendancePageController;
use App\Models\DailyPerformanceEntry;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeMonthTask;
use App\Models\LeaveRequest;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// الصفحة الرئيسية → إعادة توجيه للـ Dashboard
Route::get('/', function () {
    if (auth()->check() && auth()->user()?->role === 'user') {
        return redirect()->route('tasks.evaluator.index');
    }

    return redirect()->route('dashboard');
});

// ================================
// Auth Routes (من Breeze)
// ================================
require __DIR__.'/auth.php';

// ================================
// المسارات المحمية (يجب تسجيل الدخول)
// ================================
Route::middleware(['auth'])->group(function () {
    Route::get('/force-password-change', [PasswordController::class, 'showForceChange'])
        ->name('password.force-change');
    Route::put('/force-password-change', [PasswordController::class, 'forceChange'])
        ->name('password.force-change.update');
});

Route::middleware(['auth', 'force_password_change'])->group(function () {

    Route::get('/media/avatar/{path}', [MyAccountController::class, 'avatar'])
        ->where('path', '.*')
        ->name('media.avatar');

    Route::get('/media/daily-performance/{path}', [DailyPerformanceEmployeeController::class, 'media'])
        ->where('path', '.*')
        ->name('media.daily-performance.file');

    Route::get('/media/task-attachment/{path}', [MyAccountController::class, 'taskAttachment'])
        ->where('path', '.*')
        ->name('media.task-attachment.file');

    // Dashboard
    Route::middleware(['role:admin,manager,hr,department_manager,employee,office_girl'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // My Account
    Route::get('/my-account', [MyAccountController::class, 'show'])->name('account.my');
    Route::put('/my-account', [MyAccountController::class, 'updateProfile'])->name('account.my.update');

    // Employee of Month - Voting endpoint (employee + admin/manager/hr)
    Route::middleware(['role:employee,admin,manager,hr,department_manager'])->group(function () {
        Route::get('/employee-of-month/vote', [EmployeeOfMonthVoteController::class, 'page'])
            ->name('employee-of-month.vote.page');
        Route::get('/employee-of-month/vote/status', [EmployeeOfMonthVoteController::class, 'status'])
            ->name('employee-of-month.vote.status');
        Route::post('/employee-of-month/vote', [EmployeeOfMonthVoteController::class, 'store'])
            ->name('employee-of-month.vote.store');
    });

    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::get('/employee-of-month/admin', [EmployeeOfMonthAdminController::class, 'index'])
            ->name('employee-of-month.admin.index');
        Route::get('/employee-of-month/admin/export', [EmployeeOfMonthAdminController::class, 'exportRanking'])
            ->name('employee-of-month.admin.export');
        Route::post('/employee-of-month/admin/score', [EmployeeOfMonthAdminController::class, 'upsertScore'])
            ->name('employee-of-month.admin.score.upsert');
        Route::post('/employee-of-month/admin/finalize', [EmployeeOfMonthAdminController::class, 'finalize'])
            ->name('employee-of-month.admin.finalize');
    });

    Route::middleware(['role:admin,manager,hr,department_manager', 'can:viewAny,'.EmployeeMonthTask::class])->group(function () {
        // Tasks management (Admin/Manager/HR/Department Manager)
        Route::get('/tasks/admin', [TaskAdminController::class, 'index'])
            ->name('tasks.admin.index');
        Route::post('/tasks/admin', [TaskAdminController::class, 'store'])
            ->name('tasks.admin.store');
        Route::put('/tasks/admin/{task}', [TaskAdminController::class, 'update'])
            ->middleware('can:update,task')
            ->name('tasks.admin.update');
        Route::delete('/tasks/admin/{task}', [TaskAdminController::class, 'destroy'])
            ->middleware('can:delete,task')
            ->name('tasks.admin.destroy');
        Route::patch('/tasks/admin/{task}/toggle', [TaskAdminController::class, 'toggle'])
            ->middleware('can:update,task')
            ->name('tasks.admin.toggle');
        Route::get('/tasks/admin/export', [TaskAdminController::class, 'export'])
            ->name('tasks.admin.export');
    });

    // Evaluator user tasks page
    Route::middleware(['role:user'])->group(function () {
        Route::get('/tasks/evaluator', [TaskEvaluationController::class, 'index'])
            ->name('tasks.evaluator.index');
        Route::get('/tasks/evaluator/export', [TaskEvaluationController::class, 'export'])
            ->name('tasks.evaluator.export');
        Route::post('/tasks/evaluator/{task}/evaluate', [TaskEvaluationController::class, 'upsert'])
            ->name('tasks.evaluator.upsert');
    });

    // Employee tasks page
    Route::middleware(['role:employee'])->group(function () {
        Route::get('/tasks/my', [EmployeeMyTasksController::class, 'index'])
            ->name('tasks.my.index');
        Route::patch('/tasks/my/{task}/status', [EmployeeMyTasksController::class, 'updateStatus'])
            ->name('tasks.my.status.update');

        Route::get('/daily-performance', [DailyPerformanceEmployeeController::class, 'index'])
            ->name('daily-performance.employee.index');
        Route::post('/daily-performance', [DailyPerformanceEmployeeController::class, 'upsert'])
            ->name('daily-performance.employee.upsert');
        Route::delete('/daily-performance/attachments/{attachment}', [DailyPerformanceEmployeeController::class, 'destroyAttachment'])
            ->name('daily-performance.employee.attachment.destroy');
    });

    Route::middleware(['role:employee,office_girl,department_manager,hr,admin,manager'])->group(function () {
        Route::get('/leave/requests', [LeaveRequestController::class, 'index'])
            ->name('leave.requests.index');
        Route::post('/leave/requests', [LeaveRequestController::class, 'store'])
            ->name('leave.requests.store');
    });

    Route::middleware(['role:hr,admin,manager,department_manager', 'can:viewAny,'.LeaveRequest::class])->group(function () {
        Route::get('/leave/approvals', [LeaveApprovalController::class, 'index'])
            ->name('leave.approvals.index');
        Route::post('/leave/approvals/{leaveRequest}/decide', [LeaveApprovalController::class, 'decide'])
            ->middleware('can:approve,leaveRequest')
            ->name('leave.approvals.decide');
    });

    Route::middleware(['role:hr,admin,manager'])->group(function () {
        Route::get('/leave/approvals/employee-settings', [LeaveApprovalController::class, 'employeeSettings'])
            ->name('leave.approvals.employee-settings');
        Route::post('/leave/approvals/employee-settings/bulk-update', [LeaveApprovalController::class, 'bulkUpdateEmployeeSettings'])
            ->name('leave.approvals.employee-settings.bulk-update');
        Route::patch('/leave/approvals/employee-settings/{employee}', [LeaveApprovalController::class, 'updateEmployeeSetting'])
            ->name('leave.approvals.employee-settings.update');
        Route::post('/leave/approvals/employee-settings/apply-defaults', [LeaveApprovalController::class, 'applyDefaultEmployeeSettings'])
            ->name('leave.approvals.employee-settings.apply-defaults');
    });

    // Daily performance review page
    Route::middleware(['role:admin,manager,hr,user,department_manager', 'can:viewAny,'.DailyPerformanceEntry::class])->group(function () {
        Route::get('/daily-performance/review', [DailyPerformanceReviewController::class, 'index'])
            ->name('daily-performance.review.index');
        Route::post('/daily-performance/review/{entry}/upsert', [DailyPerformanceReviewController::class, 'upsert'])
            ->middleware('can:review,entry')
            ->name('daily-performance.review.upsert');
    });

    // ================================
    // الموظفين (Admin فقط للإضافة/التعديل)
    // ================================
    Route::middleware(['role:admin,manager,hr,department_manager,employee,office_girl,user', 'can:viewAny,'.Employee::class])->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/all-cards', [EmployeeController::class, 'allCards'])->name('employees.all-cards');
    });

    Route::middleware(['role:admin,manager,hr,department_manager', 'can:viewAny,'.Employee::class])->group(function () {
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
            ->middleware('can:view,employee')
            ->whereNumber('employee')
            ->name('employees.show');
    });

    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])
            ->middleware('can:viewAny,'.Department::class)
            ->name('departments.index');
        Route::get('/departments/create', [DepartmentController::class, 'create'])
            ->middleware('can:create,'.Department::class)
            ->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])
            ->middleware('can:create,'.Department::class)
            ->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->middleware('can:update,department')
            ->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])
            ->middleware('can:update,department')
            ->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
            ->middleware('can:delete,department')
            ->name('departments.destroy');

        Route::get('/job-titles', [JobTitleController::class, 'index'])
            ->name('job-titles.index');
        Route::get('/job-titles/create', [JobTitleController::class, 'create'])
            ->name('job-titles.create');
        Route::post('/job-titles', [JobTitleController::class, 'store'])
            ->name('job-titles.store');
        Route::get('/job-titles/{jobTitle}/edit', [JobTitleController::class, 'edit'])
            ->name('job-titles.edit');
        Route::put('/job-titles/{jobTitle}', [JobTitleController::class, 'update'])
            ->name('job-titles.update');
        Route::delete('/job-titles/{jobTitle}', [JobTitleController::class, 'destroy'])
            ->name('job-titles.destroy');
        Route::patch('/job-titles/{jobTitle}/toggle', [JobTitleController::class, 'toggle'])
            ->name('job-titles.toggle');

        // المواقع المعتمدة
        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::get('/locations/create', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
    });

    Route::middleware(['can:create,'.Employee::class])->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    });

    Route::middleware(['can:update,employee'])->group(function () {
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    });

    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])
        ->middleware('can:delete,employee')
        ->name('employees.destroy');

    // ================================
    // الاستيراد (Admin فقط)
    // ================================
    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::get('/import', [ImportController::class, 'showForm'])->name('import.form');
        Route::post('/import/upload', [ImportController::class, 'upload'])->name('import.upload');
        Route::get('/import/{batch}/confirm', [ImportController::class, 'showConfirm'])->name('import.confirm.show');
        Route::post('/import/{batch}/confirm', [ImportController::class, 'confirm'])->name('import.confirm');
        Route::delete('/import/{batch}', [ImportController::class, 'destroy'])->name('import.destroy');
        Route::get('/import/history', [ImportController::class, 'history'])->name('import.history');

        // الإجازات الرسمية
        Route::post('/import/{batch}/holidays', [PublicHolidayController::class, 'store'])->name('holidays.store');
        Route::delete('/import/{batch}/holidays/{holiday}', [PublicHolidayController::class, 'destroy'])->name('holidays.destroy');
    });

    // ================================
    // الحضور والانصراف
    // ================================
    Route::middleware(['role:employee,office_girl,department_manager,hr,admin,manager'])->group(function () {
        Route::get('/attendance/remote', [EmployeeRemoteAttendancePageController::class, 'index'])
            ->name('attendance.remote.page');
        Route::post('/attendance/check-in', [RemoteAttendanceController::class, 'checkIn'])
            ->name('attendance.check-in');
        Route::post('/attendance/check-out', [RemoteAttendanceController::class, 'checkOut'])
            ->name('attendance.check-out');
    });

    Route::middleware(['role:admin,manager,hr,department_manager'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('/attendance/employee/{employee}', [AttendanceController::class, 'employeeReport'])
            ->middleware('can:view,employee')
            ->name('attendance.employee');
        Route::get('/attendance/employee/{employee}/export', [AttendanceController::class, 'exportEmployee'])
            ->middleware('can:view,employee')
            ->name('attendance.employee.export');
    });

    // تغيير حالة يوم (للمديرين فقط)
    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::patch('/attendance/employee/{employee}/{date}/status', [AttendanceController::class, 'updateDayStatus'])
            ->name('attendance.record.status')
            ->where('date', '\d{4}-\d{2}-\d{2}');
    });

    // ================================
    // المرتبات
    // ================================
    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/report/{month}/{year}', [PayrollController::class, 'report'])->name('payroll.report');
        Route::get('/payroll/export/{month}/{year}', [PayrollController::class, 'export'])->name('payroll.export');
    });

    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::get('/payroll/calculate', [PayrollController::class, 'showCalculateForm'])->name('payroll.calculate.form');
        Route::post('/payroll/calculate', [PayrollController::class, 'calculate'])->name('payroll.calculate');
        Route::post('/payroll/lock/{report}', [PayrollController::class, 'lock'])->name('payroll.lock');
        Route::patch('/payroll/{report}/adjustment', [PayrollController::class, 'updateAdjustment'])->name('payroll.adjustment');
        Route::patch('/payroll/{report}/exclude', [PayrollController::class, 'toggleExclusion'])->name('payroll.exclude');
        Route::delete('/payroll/{month}/{year}', [PayrollController::class, 'destroyMonth'])
            ->whereNumber('month')
            ->whereNumber('year')
            ->name('payroll.destroy.month');
    });

    // ================================
    // الإعدادات (Admin فقط)
    // ================================
    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

});
