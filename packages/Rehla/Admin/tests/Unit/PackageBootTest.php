<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Admin\AdminServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(AdminServiceProvider::class));
    }
}
