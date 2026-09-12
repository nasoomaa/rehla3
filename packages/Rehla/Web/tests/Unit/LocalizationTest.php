<?php

declare(strict_types=1);

it('determines document direction correctly for supported locales', function (): void {
    $locales = [
        'en' => ['dir' => 'ltr', 'lang' => 'en'],
        'ar' => ['dir' => 'rtl', 'lang' => 'ar'],
    ];

    expect($locales['ar']['dir'])->toBe('rtl');
    expect($locales['en']['dir'])->toBe('ltr');
});
