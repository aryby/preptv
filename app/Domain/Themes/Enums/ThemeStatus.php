<?php

namespace App\Domain\Themes\Enums;

enum ThemeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
