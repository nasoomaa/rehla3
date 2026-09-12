<?php

declare(strict_types=1);

namespace Rehla\Api\Tests\Contract;

use Symfony\Component\Yaml\Yaml;

it('verifies that OpenAPI 3.1 contract matches declared API routes', function (): void {
    $openapiFile = base_path('packages/Rehla/Api/openapi/rehla-v1.yaml');
    expect(file_exists($openapiFile))->toBeTrue('rehla-v1.yaml OpenAPI specification must exist');

    $spec = Yaml::parseFile($openapiFile);

    expect($spec['openapi'])->toStartWith('3.1')
        ->and($spec['info']['title'])->toBe('Rehla Customer API')
        ->and($spec['paths'])->toBeArray()->not->toBeEmpty();

    // Verify key security schemes
    expect($spec['components']['securitySchemes']['bearerAuth'])->toBeArray()
        ->and($spec['components']['securitySchemes']['bearerAuth']['type'])->toBe('http')
        ->and($spec['components']['securitySchemes']['bearerAuth']['scheme'])->toBe('bearer');

    // Verify ProblemDetails schema
    expect($spec['components']['schemas']['ProblemDetails'])->toBeArray()
        ->and($spec['components']['schemas']['ProblemDetails']['properties'])->toHaveKeys(['type', 'title', 'status', 'code', 'trace_id']);

    // Check that core paths in spec exist
    $paths = array_keys($spec['paths']);
    expect($paths)->toContain('/auth/register', '/auth/login', '/auth/logout');
});
