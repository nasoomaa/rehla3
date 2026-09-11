<?php

declare(strict_types=1);

namespace Rehla\Reporting\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Reporting\ReportingServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(ReportingServiceProvider::class));
    }
}
