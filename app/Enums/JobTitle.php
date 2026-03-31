<?php

namespace App\Enums;

enum JobTitle: string
{
    case Designer = 'designer';
    case ThreeD = 'three_d';
    case CustomerService = 'customer_service';
    case Developer = 'developer';
    case Evaluator = 'evaluator';
    case Hr = 'hr';
    case Admin = 'admin';
    case Manager = 'manager';
    case OfficeGirl = 'office_girl';
    case Accountant = 'accountant';

    public function label(): string
    {
        return match ($this) {
            self::Designer => 'مصمم',
            self::ThreeD => '3D',
            self::CustomerService => 'خدمة عملاء',
            self::Developer => 'مبرمج',
            self::Evaluator => 'User',
            self::Hr => 'HR',
            self::Admin => 'Admin',
            self::Manager => 'مدير',
            self::OfficeGirl => 'Office Girl',
            self::Accountant => 'محاسب',
        };
    }

    public function systemRole(): string
    {
        return match ($this) {
            self::Admin => 'admin',
            self::Manager => 'manager',
            self::Hr => 'hr',
            self::Evaluator => 'user',
            self::OfficeGirl => 'office_girl',
            default => 'employee',
        };
    }
}
