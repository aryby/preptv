<?php

namespace App\Domain\Payments\Enums;

enum PaymentWebhookDisposition: string
{
    case Processed = 'processed';
    case Duplicate = 'duplicate';
    case Rejected = 'rejected';
    case Retry = 'retry';
}
