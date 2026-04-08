<?php

namespace App\Services\Leave;

use RuntimeException;

class LeaveRequestException extends RuntimeException
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
