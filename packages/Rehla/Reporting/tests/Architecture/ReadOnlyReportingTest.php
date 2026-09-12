<?php

declare(strict_types=1);

it('ensures reporting package contains no mutable Eloquent models', function (): void {
    $srcDir = base_path('packages/Rehla/Reporting/src');
    $files = glob("{$srcDir}/**/*.php");
    $modelsDir = "{$srcDir}/Models";

    expect(is_dir($modelsDir))->toBeFalse('Reporting package must not contain a Models directory');

    foreach ($files as $file) {
        $content = (string) file_get_contents($file);
        expect($content)->not->toContain('extends Model')
            ->not->toContain('extends \Illuminate\Database\Eloquent\Model');
    }
});

it('ensures reporting package performs no write operations on source tables', function (): void {
    $srcDir = base_path('packages/Rehla/Reporting/src');
    $files = glob("{$srcDir}/**/*.php") ?: [];

    // Also include top-level src files
    $topFiles = glob("{$srcDir}/*.php") ?: [];
    $allFiles = array_merge($files, $topFiles);

    $forbiddenPatterns = [
        '->insert(',
        '->update(',
        '->delete(',
        '->truncate(',
        '->save(',
        '->create(',
    ];

    foreach ($allFiles as $file) {
        $content = (string) file_get_contents($file);
        foreach ($forbiddenPatterns as $pattern) {
            expect(str_contains($content, $pattern))
                ->toBeFalse("Reporting file [{$file}] must not contain write call '{$pattern}'");
        }
    }
});
