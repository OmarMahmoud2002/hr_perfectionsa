<?php

namespace App\Services\Setting;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * مفتاح الـ Cache
     */
    private const CACHE_KEY = 'app_settings';

    /**
     * مدة الـ Cache (24 ساعة)
     */
    private const CACHE_TTL = 86400;

    private function tenantCacheKey(string $key): string
    {
        $tenant = (string) config('app.tenant', 'eg');

        return $tenant . '_' . $key;
    }

    /**
     * الإعدادات الافتراضية للنظام
     */
    public const DEFAULTS = [
        'work_start_time'          => '09:00',
        'work_end_time'            => '17:00',
        'overtime_start_time'      => '17:30',
        'late_deduction_per_hour'  => '0',
        'absent_deduction_per_day' => '0',
        'overtime_rate_per_hour'   => '0',
        'late_grace_minutes'       => '30',
        'working_days_per_month'   => '26',
        'working_hours_per_day'    => '8',
        'allow_remote_without_location' => '0',
        'default_required_work_days_before_leave' => '120',
        'default_annual_leave_days' => '21',
    ];

    /**
     * جلب جميع الإعدادات (مع Caching)
     */
    public function all(): array
    {
        return Cache::remember($this->tenantCacheKey(self::CACHE_KEY), self::CACHE_TTL, function () {
            $dbSettings = Setting::getAllAsArray();
            return array_merge(self::DEFAULTS, $dbSettings);
        });
    }

    /**
     * جلب قيمة إعداد واحد
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return $settings[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /**
     * حفظ مجموعة إعدادات
     */
    public function save(array $data, string $group = 'attendance'): void
    {
        foreach ($data as $key => $value) {
            Setting::setValue($key, $value, $group);
        }

        // مسح الـ Cache بعد الحفظ
        $this->clearCache();

        // مسح cache الـ Dashboard أيضاً (قد تتأثر الحسابات)
        Cache::forget($this->tenantCacheKey('dashboard_stats'));
    }

    /**
     * مسح الـ Cache
     */
    public function clearCache(): void
    {
        Cache::forget($this->tenantCacheKey(self::CACHE_KEY));
    }

    /**
     * جلب إعدادات مجموعة معينة
     */
    public function getGroup(string $group): array
    {
        $cacheKey = $this->tenantCacheKey("settings_group_{$group}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group) {
            return Setting::group($group)->pluck('value', 'key')->toArray();
        });
    }
}
