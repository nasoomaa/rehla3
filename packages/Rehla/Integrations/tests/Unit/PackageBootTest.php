<?php

declare(strict_types=1);

namespace Rehla\Integrations\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Integrations\IntegrationsServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(IntegrationsServiceProvider::class));
    }
}
