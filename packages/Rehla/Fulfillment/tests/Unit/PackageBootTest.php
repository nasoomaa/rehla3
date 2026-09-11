<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Fulfillment\FulfillmentServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(FulfillmentServiceProvider::class));
    }
}
