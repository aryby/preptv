<?php

namespace App\Domain\Payments\Enums;

enum PaymentEventKind: string
{
    case OrderApproved = 'order_approved';
    case OrderCancelled = 'order_cancelled';
    case CapturePending = 'capture_pending';
    case CaptureCompleted = 'capture_completed';
    case CaptureDenied = 'capture_denied';
    case CaptureRefunded = 'capture_refunded';
    case CaptureReversed = 'capture_reversed';
    case Ignored = 'ignored';
}
