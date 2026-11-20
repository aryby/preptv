<?php

namespace App\Domain\Sessions\Enums;

enum StreamingSessionStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
    case Expired = 'expired';
    case Refused = 'refused';
}
