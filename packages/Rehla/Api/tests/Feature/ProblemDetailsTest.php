<?php

declare(strict_types=1);

namespace Rehla\Api\Tests\Feature;

it('returns stable problem details independent of locale', function (): void {
    $response = $this->postJson('/api/v1/order-submissions', []);

    $response->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('type', 'https://rehla.example/problems/unauthenticated')
        ->assertJsonPath('code', 'UNAUTHENTICATED')
        ->assertJsonStructure(['type', 'title', 'status', 'code', 'trace_id']);
});

it('returns localized problem details title and detail based on Accept-Language', function (): void {
    $responseEn = $this->withHeader('Accept-Language', 'en')
        ->postJson('/api/v1/order-submissions', []);

    $responseAr = $this->withHeader('Accept-Language', 'ar')
        ->postJson('/api/v1/order-submissions', []);

    $responseEn->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');

    $responseAr->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');

    expect($responseEn->json('title'))->not->toBe($responseAr->json('title'));
});

it('returns validation problem details for invalid payloads', function (): void {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('type', 'https://rehla.example/problems/validation-error')
        ->assertJsonPath('code', 'VALIDATION_FAILED')
        ->assertJsonStructure(['type', 'title', 'status', 'code', 'trace_id', 'errors']);
});
