<?php

$mapPath = __DIR__.'/../docs/architecture/rehla-package-map.json';
$map = json_decode(file_get_contents($mapPath), true, flags: JSON_THROW_ON_ERROR);

$packagesDir = __DIR__.'/../packages/Rehla';
if (! is_dir($packagesDir)) {
    mkdir($packagesDir, 0755, true);
}

foreach ($map['packages'] as $package => $deps) {
    $pkgDir = "{$packagesDir}/{$package}";
    $srcDir = "{$pkgDir}/src";
    $testsDir = "{$pkgDir}/tests/Unit";

    @mkdir($srcDir, 0755, true);
    @mkdir($testsDir, 0755, true);

    $lowerName = strtolower($package);

    // 1. composer.json
    $requires = [
        'php' => '^8.2',
    ];
    foreach ($deps as $dep) {
        $depLower = strtolower($dep);
        $requires["rehla/{$depLower}"] = '@dev';
    }

    $composerData = [
        'name' => "rehla/{$lowerName}",
        'description' => "Rehla {$package} Package",
        'type' => 'library',
        'license' => 'proprietary',
        'require' => $requires,
        'autoload' => [
            'psr-4' => [
                "Rehla\\{$package}\\" => 'src/',
            ],
        ],
        'autoload-dev' => [
            'psr-4' => [
                "Rehla\\{$package}\\Tests\\" => 'tests/',
            ],
        ],
        'extra' => [
            'laravel' => [
                'providers' => [
                    "Rehla\\{$package}\\{$package}ServiceProvider",
                ],
            ],
        ],
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
    ];

    file_put_contents(
        "{$pkgDir}/composer.json",
        json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
    );

    // 2. ServiceProvider
    $providerContent = <<<PHP
<?php

declare(strict_types=1);

namespace Rehla\\{$package};

use Illuminate\Support\ServiceProvider;

final class {$package}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}

PHP;
    file_put_contents("{$srcDir}/{$package}ServiceProvider.php", $providerContent);

    // 3. README.md
    $readmeContent = <<<MARKDOWN
# Rehla {$package} Package

Part of the Rehla Modular Monolith platform.

## Dependencies
PHP 8.5, Laravel 13.x
Declared imports:
MARKDOWN;
    foreach ($deps as $dep) {
        $readmeContent .= "\n- `Rehla\\{$dep}`";
    }
    $readmeContent .= "\n";
    file_put_contents("{$pkgDir}/README.md", $readmeContent);

    // 4. PackageBootTest.php
    $testContent = <<<PHP
<?php

declare(strict_types=1);

namespace Rehla\\{$package}\\Tests\\Unit;

use PHPUnit\Framework\TestCase;
use Rehla\\{$package}\\{$package}ServiceProvider;

final class PackageBootTest extends TestCase
{
    public function test_package_provider_exists(): void
    {
        \$this->assertTrue(class_exists({$package}ServiceProvider::class));
    }
}

PHP;
    file_put_contents("{$testsDir}/PackageBootTest.php", $testContent);
}

echo "Created 19 Rehla packages in {$packagesDir}\n";
