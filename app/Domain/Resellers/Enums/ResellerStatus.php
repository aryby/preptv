<?php

namespace App\Domain\Resellers\Enums;

enum ResellerStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
