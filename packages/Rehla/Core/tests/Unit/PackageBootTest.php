<?php

declare(strict_types=1);

namespace Rehla\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Core\CoreServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(CoreServiceProvider::class));
    }
}
