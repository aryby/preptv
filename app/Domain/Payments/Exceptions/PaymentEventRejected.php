<?php

namespace App\Domain\Payments\Exceptions;

use RuntimeException;

class PaymentEventRejected extends RuntimeException
{
    public function __construct(public readonly string $failureCode)
    {
        parent::__construct($failureCode);
    }
}
