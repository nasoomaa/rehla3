<?php

declare(strict_types=1);

namespace Rehla\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\Notifications\NotificationsServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        $this->assertTrue(class_exists(NotificationsServiceProvider::class));
    }
}
