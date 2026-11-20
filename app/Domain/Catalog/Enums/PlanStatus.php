<?php

namespace App\Domain\Catalog\Enums;

enum PlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
}
