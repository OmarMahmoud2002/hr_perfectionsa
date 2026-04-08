<?php

namespace App\Http\Controllers;

use App\Services\Setting\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    public function index()
    {
        $settings = $this->settingService->all();

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'work_start_time'        => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'work_end_time'          => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'overtime_start_time'    => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'late_grace_minutes'     => ['required', 'integer', 'min:0', 'max:120'],
            'working_days_per_month' => ['required', 'integer', 'min:1', 'max:31'],
            'working_hours_per_day'  => ['required', 'numeric', 'min:1', 'max:24'],
            'default_required_work_days_before_leave' => ['required', 'integer', 'min:0', 'max:3650'],
            'default_annual_leave_days' => ['required', 'integer', 'min:0', 'max:365'],
        ], [
            'work_start_time.required'        => 'وقت بدء العمل مطلوب.',
            'work_start_time.regex'           => 'صيغة وقت بدء العمل غير صحيحة.',
            'work_end_time.required'          => 'وقت انتهاء العمل مطلوب.',
            'work_end_time.regex'             => 'صيغة وقت انتهاء العمل غير صحيحة.',
            'overtime_start_time.required'    => 'وقت بدء الأوفرتايم مطلوب.',
            'overtime_start_time.regex'       => 'صيغة وقت بدء الأوفرتايم غير صحيحة.',
            'late_grace_minutes.required'     => 'فترة السماح للتأخير مطلوبة.',
            'late_grace_minutes.integer'      => 'فترة السماح يجب أن تكون عدداً صحيحاً.',
            'working_days_per_month.required' => 'عدد أيام العمل في الشهر مطلوب.',
            'working_hours_per_day.required'  => 'عدد ساعات العمل في اليوم مطلوب.',
            'default_required_work_days_before_leave.required' => 'عدد أيام الخدمة المطلوب قبل الإجازة مطلوب.',
            'default_required_work_days_before_leave.integer' => 'عدد أيام الخدمة المطلوب قبل الإجازة يجب أن يكون عدداً صحيحاً.',
            'default_annual_leave_days.required' => 'الرصيد السنوي الافتراضي للإجازة مطلوب.',
            'default_annual_leave_days.integer' => 'الرصيد السنوي الافتراضي يجب أن يكون عدداً صحيحاً.',
        ]);

        $this->settingService->save($validated, 'attendance');

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
