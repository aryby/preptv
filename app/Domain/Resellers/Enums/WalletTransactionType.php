<?php

namespace App\Domain\Resellers\Enums;

enum WalletTransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Reversal = 'reversal';
}
