<?php

declare(strict_types=1);

namespace Rehla\Audit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Audit\AuditServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(AuditServiceProvider::class));
    }
}
