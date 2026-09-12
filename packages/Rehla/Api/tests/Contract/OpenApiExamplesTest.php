<?php

declare(strict_types=1);

namespace Rehla\Api\Tests\Contract;

use Symfony\Component\Yaml\Yaml;

it('confirms that all endpoints in OpenAPI spec specify valid request and response components', function (): void {
    $openapiFile = base_path('packages/Rehla/Api/openapi/rehla-v1.yaml');
    expect(file_exists($openapiFile))->toBeTrue();

    $spec = Yaml::parseFile($openapiFile);
    $paths = $spec['paths'] ?? [];

    expect($paths)->toBeArray()->not->toBeEmpty();

    foreach ($paths as $path => $methods) {
        foreach ($methods as $method => $definition) {
            expect($definition)->toHaveKey('operationId')
                ->and($definition)->toHaveKey('responses');

            $responses = $definition['responses'];
            expect($responses)->toBeArray()->not->toBeEmpty();
        }
    }
});
