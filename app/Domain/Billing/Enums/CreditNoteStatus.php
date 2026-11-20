<?php

namespace App\Domain\Billing\Enums;

enum CreditNoteStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Void = 'void';
}
