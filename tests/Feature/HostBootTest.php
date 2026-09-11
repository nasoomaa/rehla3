<?php

it('boots the Rehla host in testing mode', function (): void {
    expect(app()->environment())->toBe('testing');
    expect(config('database.default'))->toBe('pgsql');
});
