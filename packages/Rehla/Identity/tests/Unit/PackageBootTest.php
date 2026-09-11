<?php

declare(strict_types=1);

namespace Rehla\Identity\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Identity\IdentityServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(IdentityServiceProvider::class));
    }
}
