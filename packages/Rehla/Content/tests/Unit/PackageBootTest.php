<?php

declare(strict_types=1);

namespace Rehla\Content\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Content\ContentServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(ContentServiceProvider::class));
    }
}
