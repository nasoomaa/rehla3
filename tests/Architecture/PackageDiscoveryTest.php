<?php

it('discovers every declared Rehla package provider', function (): void {
    $map = json_decode(file_get_contents(base_path('docs/architecture/rehla-package-map.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach (array_keys($map['packages']) as $package) {
        $provider = "Rehla\\{$package}\\{$package}ServiceProvider";
        expect(class_exists($provider))->toBeTrue("Class {$provider} does not exist");
        expect(app()->getProvider($provider))->not->toBeNull("Provider {$provider} is not registered in app container");
    }
});
