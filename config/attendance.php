<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Work Schedule Settings
    |--------------------------------------------------------------------------
    */
    'work_schedule' => [
        'work_start_time'    => '09:00',  // بداية الدوام الرسمي
        'work_end_time'      => '17:00',  // نهاية الدوام الرسمي
        'overtime_start_time'=> '17:30',  // بداية حساب Overtime (بعد فترة السماح)
    ],

    /*
    |--------------------------------------------------------------------------
    | Payroll Settings
    |--------------------------------------------------------------------------
    */
    'payroll' => [
        'late_deduction_per_hour'   => 0,   // قيمة خصم ساعة التأخير (بالجنيه)
        'overtime_rate_per_hour'    => 0,   // قيمة ساعة Overtime (بالجنيه)
        'absent_deduction_per_day'  => 0,   // قيمة خصم يوم الغياب (بالجنيه)
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */
    'import' => [
        'max_file_size'      => 10240,  // الحد الأقصى لحجم الملف بالكيلوبايت (10MB)
        'allowed_extensions' => ['xlsx', 'xls'],
        'storage_path'       => 'imports',
    ],

    /*
    |--------------------------------------------------------------------------
    | Days of Week
    |--------------------------------------------------------------------------
    | 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday
    | الجمعة (5) لا تظهر في ملف Excel أصلاً
    */
    'days' => [
        'friday' => 5,  // يوم الجمعة - إجازة ثابتة لا تظهر في الملف
    ],

    /*
    |--------------------------------------------------------------------------
    | Employee Account Settings
    |--------------------------------------------------------------------------
    */
    'employee_accounts' => [
        'email_domain'     => 'perfection.com',
        'initial_password' => '123456789',
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Database Tenancy By Domain
    |--------------------------------------------------------------------------
    */
    'tenancy' => [
        'fallback_tenant' => env('TENANCY_FALLBACK_TENANT', 'eg'),
        'domain_connection_map' => [
            'hr.perfectionsa.com' => ['tenant' => 'eg', 'connection' => 'mysql_eg'],
            'hrsa.perfectionsa.com' => ['tenant' => 'sa', 'connection' => 'mysql_sa'],
            'localhost' => ['tenant' => 'eg', 'connection' => 'mysql_eg'],
            'hr.localhost' => ['tenant' => 'eg', 'connection' => 'mysql_eg'],
            'hrsa.localhost' => ['tenant' => 'sa', 'connection' => 'mysql_sa'],
        ],
    ],

];
