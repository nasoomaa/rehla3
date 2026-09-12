<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Architecture;

it('ensures admin package contains no mutable Eloquent models', function (): void {
    $srcDir = base_path('packages/Rehla/Admin/src');
    $files = glob("{$srcDir}/**/*.php") ?: [];
    $modelsDir = "{$srcDir}/Models";

    expect(is_dir($modelsDir))->toBeFalse('Admin package must not contain a Models directory');

    foreach ($files as $file) {
        $content = (string) file_get_contents($file);
        expect($content)
            ->not->toContain('extends Model')
            ->not->toContain('extends \Illuminate\Database\Eloquent\Model');
    }
});

it('ensures admin package performs zero direct database queries or writes', function (): void {
    $srcDir = base_path('packages/Rehla/Admin/src');
    $files = glob("{$srcDir}/**/*.php") ?: [];
    $topFiles = glob("{$srcDir}/*.php") ?: [];
    $allFiles = array_merge($files, $topFiles);

    $forbiddenPatterns = [
        'DB::table',
        'DB::raw',
        'DB::select',
        'DB::statement',
        'DB::insert',
        'DB::update',
        'DB::delete',
        '->save(',
        '->insert(',
        '->update(',
        '->delete(',
        '->truncate(',
    ];

    foreach ($allFiles as $file) {
        $content = (string) file_get_contents($file);
        foreach ($forbiddenPatterns as $pattern) {
            expect(str_contains($content, $pattern))
                ->toBeFalse("Admin file [{$file}] must not contain direct DB write/query '{$pattern}'");
        }
    }
});

it('ensures admin package imports no cross-package Eloquent models', function (): void {
    $srcDir = base_path('packages/Rehla/Admin/src');
    $files = glob("{$srcDir}/**/*.php") ?: [];
    $topFiles = glob("{$srcDir}/*.php") ?: [];
    $allFiles = array_merge($files, $topFiles);

    foreach ($allFiles as $file) {
        $content = (string) file_get_contents($file);
        expect($content)->not->toMatch('/use\s+Rehla\\\\[A-Za-z0-9_]+\\\\Models\\\\/');
    }
});
