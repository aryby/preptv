<?php

namespace App\Domain\Billing\Exceptions;

use RuntimeException;

class InvoiceProcessingException extends RuntimeException
{
    public function __construct(public readonly string $failureCode)
    {
        parent::__construct($failureCode);
    }
}
