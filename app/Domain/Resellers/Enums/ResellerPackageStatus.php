<?php

namespace App\Domain\Resellers\Enums;

enum ResellerPackageStatus: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Inactive = 'inactive';
    case Expired = 'expired';
}
