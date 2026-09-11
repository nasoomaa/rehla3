<?php

declare(strict_types=1);

use Tests\Architecture\Support\ArchitectureScanner;

it('forbids cross-package Eloquent model imports across all packages', function (): void {
    $packagesDir = base_path('packages/Rehla');
    $violations = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($packagesDir));
    foreach ($iterator as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }

        // Determine current package name from file path: packages/Rehla/<Package>/src/...
        $relativePath = str_replace($packagesDir . '/', '', $file->getPathname());
        $parts = explode('/', $relativePath);
        $currentPackage = $parts[0] ?? '';

        $references = ArchitectureScanner::scanFileReferences($file->getPathname());
        foreach ($references as $ref) {
            $referencedClass = $ref['referenced'];
            $targetPackage = $ref['package'];

            // Self models are fine
            if ($targetPackage === $currentPackage) {
                continue;
            }

            // Detect cross-package Models
            if (str_contains($referencedClass, '\\Models\\')) {
                $violations[] = "[Model Boundary Violation] {$file->getPathname()}:{$ref['line']} Package '{$currentPackage}' must not import model '{$referencedClass}' from package '{$targetPackage}'";
            }
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});

it('detects cross-package model imports across direct, grouped, and inline expressions while permitting contracts', function (): void {
    // 1. Direct cross-package model
    $codeDirect = <<<'PHP'
    <?php
    namespace Rehla\Purchasing\Actions;
    use Rehla\Wallet\Models\Wallet;
    PHP;
    $refsDirect = ArchitectureScanner::scanCodeReferences($codeDirect, 'PurchasingAction.php');
    $isModelLeak = collect($refsDirect)->contains(fn ($r) => str_contains($r['referenced'], '\\Models\\'));
    expect($isModelLeak)->toBeTrue();

    // 2. Grouped cross-package models
    $codeGrouped = <<<'PHP'
    <?php
    namespace Rehla\Purchasing\Actions;
    use Rehla\Wallet\Models\{Wallet, LedgerEntry};
    PHP;
    $refsGrouped = ArchitectureScanner::scanCodeReferences($codeGrouped, 'PurchasingAction.php');
    $modelLeaks = collect($refsGrouped)->filter(fn ($r) => str_contains($r['referenced'], '\\Models\\'));
    expect($modelLeaks->count())->toBe(2);

    // 3. Inline new instantiation
    $codeInline = <<<'PHP'
    <?php
    namespace Rehla\Purchasing\Actions;
    $model = new \Rehla\Orders\Models\Order();
    PHP;
    $refsInline = ArchitectureScanner::scanCodeReferences($codeInline, 'PurchasingAction.php');
    $isInlineLeak = collect($refsInline)->contains(fn ($r) => str_contains($r['referenced'], '\\Models\\'));
    expect($isInlineLeak)->toBeTrue();

    // 4. Permitted contract: Rehla\Wallet\Contracts\DebitWallet
    $codeContract = <<<'PHP'
    <?php
    namespace Rehla\Purchasing\Actions;
    use Rehla\Wallet\Contracts\DebitWallet;
    PHP;
    $refsContract = ArchitectureScanner::scanCodeReferences($codeContract, 'PurchasingAction.php');
    $isContractModelLeak = collect($refsContract)->contains(fn ($r) => str_contains($r['referenced'], '\\Models\\'));
    expect($isContractModelLeak)->toBeFalse();
});
