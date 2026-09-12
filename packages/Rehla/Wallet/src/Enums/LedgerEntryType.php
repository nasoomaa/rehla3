<?php

declare(strict_types=1);

namespace Rehla\Wallet\Enums;

enum LedgerEntryType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Reversal = 'reversal';
}
