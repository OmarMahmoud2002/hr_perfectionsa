<?php

namespace App\Enums;

enum TaskAssignmentStatus: string
{
    case ToDo = 'to_do';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::ToDo => 'To Do',
            self::InProgress => 'In Progress',
            self::Done => 'Done',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
