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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// الصفحة الرئيسية → إعادة توجيه للـ Dashboard
Route::get('/', function () {
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

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ================================
    // الموظفين (Admin فقط للإضافة/التعديل)
    // ================================
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

    Route::middleware(['role:admin'])->group(function () {
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
    Route::middleware(['role:admin'])->group(function () {
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
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    Route::get('/attendance/employee/{employee}', [AttendanceController::class, 'employeeReport'])->name('attendance.employee');
    Route::get('/attendance/employee/{employee}/export', [AttendanceController::class, 'exportEmployee'])->name('attendance.employee.export');

    // تغيير حالة يوم (للمديرين فقط)
    Route::middleware(['role:admin'])->group(function () {
        Route::patch('/attendance/employee/{employee}/{date}/status', [AttendanceController::class, 'updateDayStatus'])
            ->name('attendance.record.status')
            ->where('date', '\d{4}-\d{2}-\d{2}');
    });

    // ================================
    // المرتبات
    // ================================
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/report/{month}/{year}', [PayrollController::class, 'report'])->name('payroll.report');
    Route::get('/payroll/export/{month}/{year}', [PayrollController::class, 'export'])->name('payroll.export');

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/payroll/calculate', [PayrollController::class, 'showCalculateForm'])->name('payroll.calculate.form');
        Route::post('/payroll/calculate', [PayrollController::class, 'calculate'])->name('payroll.calculate');
        Route::post('/payroll/lock/{report}', [PayrollController::class, 'lock'])->name('payroll.lock');
        Route::patch('/payroll/{report}/adjustment', [PayrollController::class, 'updateAdjustment'])->name('payroll.adjustment');
    });

    // ================================
    // الإعدادات (Admin فقط)
    // ================================
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

});


require __DIR__.'/auth.php';
