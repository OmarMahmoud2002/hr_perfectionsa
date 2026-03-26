<?php

namespace App\Services\EmployeeOfMonth;

use RuntimeException;

class EmployeeOfMonthVoteException extends RuntimeException
{
    public function __construct(
        private readonly string $reason,
        string $message
    ) {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
