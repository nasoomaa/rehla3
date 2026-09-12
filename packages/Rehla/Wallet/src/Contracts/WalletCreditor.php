<?php

declare(strict_types=1);

namespace Rehla\Wallet\Contracts;

use Rehla\Wallet\Data\CreditResult;
use Rehla\Wallet\Data\CreditWalletData;

interface WalletCreditor
{
    public function credit(CreditWalletData $data): CreditResult;
}
