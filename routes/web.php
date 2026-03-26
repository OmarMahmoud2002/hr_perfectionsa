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

    // Dashboard
    Route::middleware(['role:admin,manager,hr,employee'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // My Account
    Route::get('/my-account', [MyAccountController::class, 'show'])->name('account.my');
    Route::put('/my-account', [MyAccountController::class, 'updateProfile'])->name('account.my.update');

    // Employee of Month - Voting endpoint (employee + admin/manager/hr)
    Route::middleware(['role:employee,admin,manager,hr'])->group(function () {
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

        // Tasks management (Admin/Manager/HR)
        Route::get('/tasks/admin', [TaskAdminController::class, 'index'])
            ->name('tasks.admin.index');
        Route::post('/tasks/admin', [TaskAdminController::class, 'store'])
            ->name('tasks.admin.store');
        Route::put('/tasks/admin/{task}', [TaskAdminController::class, 'update'])
            ->name('tasks.admin.update');
        Route::patch('/tasks/admin/{task}/toggle', [TaskAdminController::class, 'toggle'])
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
    });

    // ================================
    // الموظفين (Admin فقط للإضافة/التعديل)
    // ================================
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });

    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

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
    Route::middleware(['role:admin,manager,hr'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('/attendance/employee/{employee}', [AttendanceController::class, 'employeeReport'])->name('attendance.employee');
        Route::get('/attendance/employee/{employee}/export', [AttendanceController::class, 'exportEmployee'])->name('attendance.employee.export');
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
