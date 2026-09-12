<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

it('separates liveness from readiness', function (): void {
    // 1. Liveness probe /up responds without external dependencies
    $this->getJson('/up')
        ->assertOk()
        ->assertJson(['status' => 'alive']);

    // 2. Readiness probe /ready verifies PostgreSQL and storage metadata
    Storage::fake('private');

    $this->getJson('/ready')
        ->assertOk()
        ->assertJsonPath('status', 'ready')
        ->assertJsonPath('checks.database', 'ok')
        ->assertJsonPath('checks.storage', 'ok')
        ->assertJsonPath('checks.cache', 'ok');
});

it('returns 503 from readiness probe when database check fails', function (): void {
    $this->getJson('/ready?simulate_db_down=1')
        ->assertStatus(503)
        ->assertJsonPath('status', 'unhealthy')
        ->assertJsonPath('checks.database', 'failed');
});
