<?php

declare(strict_types=1);

namespace Rehla\Wallet\Contracts;

use Rehla\Wallet\Data\DebitResult;
use Rehla\Wallet\Data\DebitWalletData;

interface WalletDebitor
{
    public function debit(DebitWalletData $data): DebitResult;
}
