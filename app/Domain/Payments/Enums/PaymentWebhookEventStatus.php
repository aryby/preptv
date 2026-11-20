<?php

namespace App\Domain\Payments\Enums;

enum PaymentWebhookEventStatus: string
{
    case Received = 'received';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Rejected = 'rejected';
}
