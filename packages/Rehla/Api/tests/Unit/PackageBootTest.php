<?php

declare(strict_types=1);

namespace Rehla\Api\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Api\ApiServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(ApiServiceProvider::class));
    }
}
