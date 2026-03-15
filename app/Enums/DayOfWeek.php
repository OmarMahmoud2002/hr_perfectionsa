<?php

namespace App\Enums;

enum DayOfWeek: int
{
    case Sunday    = 0;
    case Monday    = 1;
    case Tuesday   = 2;
    case Wednesday = 3;
    case Thursday  = 4;
    case Friday    = 5;  // إجازة ثابتة - لا تظهر في ملف Excel
    case Saturday  = 6;

    public function label(): string
    {
        return match($this) {
            self::Sunday    => 'الأحد',
            self::Monday    => 'الاثنين',
            self::Tuesday   => 'الثلاثاء',
            self::Wednesday => 'الأربعاء',
            self::Thursday  => 'الخميس',
            self::Friday    => 'الجمعة',
            self::Saturday  => 'السبت',
        };
    }

    /**
     * أيام العمل المتاحة كيوم إجازة ثانٍ (عدا الجمعة التي هي إجازة ثابتة)
     */
    public static function availableDaysOff(): array
    {
        return [
            self::Sunday,
            self::Monday,
            self::Tuesday,
            self::Wednesday,
            self::Thursday,
            self::Saturday,
        ];
    }
}
