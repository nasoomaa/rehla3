<?php

use Illuminate\Support\LazyCollection;

it('maps every product section and mandatory atomic family', function (): void {
    $rows = LazyCollection::make(fn () => yield from array_map('str_getcsv', file(base_path('docs/requirements/rehla-phase-1-acceptance.csv'))));
    $records = $rows->skip(1)->values();
    $ids = $records->pluck(0);

    foreach (range(1, 65) as $number) {
        expect($ids->contains(fn (string $id): bool => str_starts_with($id, sprintf('R%02d', $number))))->toBeTrue();
    }

    foreach (['R08.01', 'R08.11', 'R40.01', 'R40.14', 'R50.05', 'R62.01', 'R62.12', 'R63.W13', 'R63.A09'] as $id) {
        expect($ids)->toContain($id);
    }
});
