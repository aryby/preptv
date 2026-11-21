<?php

namespace App\Domain\Payments\Exceptions;

use RuntimeException;
use Throwable;

class PaymentGatewayException extends RuntimeException
{
    public function __construct(
        public readonly string $failureCode,
        string $message = 'Le prestataire de paiement est temporairement indisponible.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
