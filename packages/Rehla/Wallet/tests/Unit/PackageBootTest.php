<?php

declare(strict_types=1);

namespace Rehla\Wallet\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Wallet\WalletServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(WalletServiceProvider::class));
    }
}
