<?php

declare(strict_types=1);

use Tests\Architecture\Support\ArchitectureScanner;

it('enforces package dependency map and forbids cycles and invalid imports', function (): void {
    $packageMap = ArchitectureScanner::loadPackageMap();
    $packagesDir = base_path('packages/Rehla');
    $violations = [];

    foreach ($packageMap as $packageName => $allowedDeps) {
        $srcDir = "{$packagesDir}/{$packageName}/src";
        if (!is_dir($srcDir)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $references = ArchitectureScanner::scanFileReferences($file->getPathname());
            foreach ($references as $ref) {
                $target = $ref['package'];

                // Self-references are allowed
                if ($target === $packageName) {
                    continue;
                }

                // Core imports are allowed for all except Core itself
                if ($target === 'Core' && $packageName !== 'Core') {
                    continue;
                }

                // Core cannot import any other Rehla package
                if ($packageName === 'Core') {
                    $violations[] = "[Core Violation] {$file->getPathname()}:{$ref['line']} Core must not import Rehla\\{$target}";
                    continue;
                }

                // Check allowed declared dependencies
                if (!in_array($target, $allowedDeps, true)) {
                    $violations[] = "[Dependency Violation] {$file->getPathname()}:{$ref['line']} Package '{$packageName}' is not permitted to import 'Rehla\\{$target}'";
                }
            }
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});

it('detects forbidden package imports across direct, grouped, and qualified inline fixtures', function (): void {
    // 1. Direct forbidden use statement
    $codeDirect = <<<'PHP'
    <?php
    namespace Rehla\Wallet\Services;
    use Rehla\Admin\Resources\OrderResource;
    PHP;
    $refsDirect = ArchitectureScanner::scanCodeReferences($codeDirect, 'WalletService.php');
    $targetsDirect = array_column($refsDirect, 'package');
    expect($targetsDirect)->toContain('Admin');

    // 2. Grouped forbidden use statement
    $codeGrouped = <<<'PHP'
    <?php
    namespace Rehla\Orders\Actions;
    use Rehla\Admin\{OrderResource, UserResource};
    PHP;
    $refsGrouped = ArchitectureScanner::scanCodeReferences($codeGrouped, 'OrderAction.php');
    $targetsGrouped = array_column($refsGrouped, 'package');
    expect($targetsGrouped)->toContain('Admin');

    // 3. Inline qualified forbidden reference
    $codeInline = <<<'PHP'
    <?php
    namespace Rehla\Catalog\Services;
    $res = \Rehla\Admin\Resources\OrderResource::make();
    PHP;
    $refsInline = ArchitectureScanner::scanCodeReferences($codeInline, 'CatalogService.php');
    $targetsInline = array_column($refsInline, 'package');
    expect($targetsInline)->toContain('Admin');
});
