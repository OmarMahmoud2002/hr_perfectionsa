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
            'allow_remote_without_location' => ['nullable', 'boolean'],
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
        ]);

        $settingsPayload = [
            'work_start_time' => $validated['work_start_time'],
            'work_end_time' => $validated['work_end_time'],
            'overtime_start_time' => $validated['overtime_start_time'],
            'late_grace_minutes' => $validated['late_grace_minutes'],
            'working_days_per_month' => $validated['working_days_per_month'],
            'working_hours_per_day' => $validated['working_hours_per_day'],
            'allow_remote_without_location' => isset($validated['allow_remote_without_location'])
                ? (string) (int) ((bool) $validated['allow_remote_without_location'])
                : '0',
        ];

        $this->settingService->save($settingsPayload, 'attendance');

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
