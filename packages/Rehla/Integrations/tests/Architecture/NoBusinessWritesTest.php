<?php

declare(strict_types=1);

it('enforces zero business database writes and no models in Integrations package', function (): void {
    $integrationsSrc = base_path('packages/Rehla/Integrations/src');

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($integrationsSrc));
    $forbiddenTokens = ['DB::table', 'DB::insert', 'DB::update', 'DB::delete', 'extends Model'];

    $violations = [];
    foreach ($iterator as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        foreach ($forbiddenTokens as $token) {
            if (str_contains($contents, $token)) {
                $violations[] = "{$file->getFilename()} contains forbidden pattern: {$token}";
            }
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));

    // Ensure no Models directory exists
    expect(is_dir(base_path('packages/Rehla/Integrations/src/Models')))->toBeFalse();
});
