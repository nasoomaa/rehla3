<?php

declare(strict_types=1);

it('enforces that every table has exactly one owning package in table-ownership.json', function (): void {
    $ownershipFile = base_path('docs/architecture/table-ownership.json');
    expect(file_exists($ownershipFile))->toBeTrue('table-ownership.json must exist');

    $data = json_decode((string) file_get_contents($ownershipFile), true, flags: JSON_THROW_ON_ERROR);
    $tables = $data['tables'] ?? [];

    expect($tables)->not->toBeEmpty('tables mapping cannot be empty');

    // Verify all table names are unique keys and mapped to non-empty string package names
    $seenTables = [];
    foreach ($tables as $tableName => $owningPackage) {
        expect($tableName)->toBeString()->not->toBeEmpty();
        expect($owningPackage)->toBeString()->not->toBeEmpty();
        expect(isset($seenTables[$tableName]))->toBeFalse("Duplicate table definition for '{$tableName}'");
        $seenTables[$tableName] = $owningPackage;
    }
});

it('verifies migration Schema::create calls correspond to declared table owners', function (): void {
    $ownershipFile = base_path('docs/architecture/table-ownership.json');
    $data = json_decode((string) file_get_contents($ownershipFile), true, flags: JSON_THROW_ON_ERROR);
    $declaredTables = $data['tables'] ?? [];

    $migrationDirs = [base_path('database/migrations')];
    $packagesDir = base_path('packages/Rehla');
    if (is_dir($packagesDir)) {
        foreach (scandir($packagesDir) as $dir) {
            $pkgMig = "{$packagesDir}/{$dir}/database/migrations";
            if ($dir !== '.' && $dir !== '..' && is_dir($pkgMig)) {
                $migrationDirs[] = $pkgMig;
            }
        }
    }

    $createdTables = [];
    foreach ($migrationDirs as $dir) {
        $files = glob("{$dir}/*.php");
        foreach ($files as $file) {
            $code = (string) file_get_contents($file);
            // Match Schema::create('table_name', ...) or Schema::create("table_name", ...)
            if (preg_match_all('/Schema::create\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $code, $matches)) {
                foreach ($matches[1] as $tableName) {
                    $createdTables[$tableName][] = $file;
                }
            }
        }
    }

    // Assert that every created table in migrations is declared in table-ownership.json
    foreach ($createdTables as $tableName => $sourceFiles) {
        expect(array_key_exists($tableName, $declaredTables))
            ->toBeTrue("Table '{$tableName}' created in migration [".implode(', ', $sourceFiles).'] is not registered in table-ownership.json');
        expect(count($sourceFiles))
            ->toBe(1, "Table '{$tableName}' is created in multiple migrations: ".implode(', ', $sourceFiles));
    }
});
