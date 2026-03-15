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
            'work_start_time'          => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'work_end_time'            => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'overtime_start_time'      => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'late_deduction_per_hour'  => ['required', 'numeric', 'min:0'],
            'absent_deduction_per_day' => ['required', 'numeric', 'min:0'],
            'overtime_rate_per_hour'   => ['required', 'numeric', 'min:0'],
            'late_grace_minutes'       => ['required', 'integer', 'min:0', 'max:120'],
            'working_days_per_month'   => ['required', 'integer', 'min:1', 'max:31'],
            'working_hours_per_day'    => ['required', 'numeric', 'min:1', 'max:24'],
        ], [
            'work_start_time.required'          => 'وقت بدء العمل مطلوب.',
            'work_start_time.regex'             => 'صيغة وقت بدء العمل غير صحيحة.',
            'work_end_time.required'            => 'وقت انتهاء العمل مطلوب.',
            'work_end_time.regex'               => 'صيغة وقت انتهاء العمل غير صحيحة.',
            'overtime_start_time.required'      => 'وقت بدء الأوفرتايم مطلوب.',
            'overtime_start_time.regex'         => 'صيغة وقت بدء الأوفرتايم غير صحيحة.',
            'late_deduction_per_hour.required'  => 'قيمة خصم ساعة التأخير مطلوبة.',
            'late_deduction_per_hour.numeric'   => 'يجب أن تكون قيمة خصم ساعة التأخير رقماً.',
            'absent_deduction_per_day.required' => 'قيمة خصم يوم الغياب مطلوبة.',
            'absent_deduction_per_day.numeric'  => 'يجب أن تكون قيمة خصم يوم الغياب رقماً.',
            'overtime_rate_per_hour.required'   => 'قيمة مكافأة ساعة الأوفرتايم مطلوبة.',
            'overtime_rate_per_hour.numeric'    => 'يجب أن تكون قيمة مكافأة ساعة الأوفرتايم رقماً.',
            'late_grace_minutes.required'       => 'فترة السماح للتأخير مطلوبة.',
            'late_grace_minutes.integer'        => 'فترة السماح يجب أن تكون عدداً صحيحاً.',
            'working_days_per_month.required'   => 'عدد أيام العمل في الشهر مطلوب.',
            'working_hours_per_day.required'    => 'عدد ساعات العمل في اليوم مطلوب.',
        ]);

        $this->settingService->save($validated, 'attendance');

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
