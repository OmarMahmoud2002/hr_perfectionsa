<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'في الانتظار',
            self::Processing => 'جارِ المعالجة',
            self::Completed  => 'مكتمل',
            self::Failed     => 'فشل',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending    => 'warning',
            self::Processing => 'info',
            self::Completed  => 'success',
            self::Failed     => 'danger',
        };
    }
}
