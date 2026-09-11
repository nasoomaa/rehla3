<?php

declare(strict_types=1);

namespace Rehla\Catalog\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Catalog\CatalogServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(CatalogServiceProvider::class));
    }
}
