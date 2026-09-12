<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Identity\Enums\AbilityName;

it('registers a customer without staff powers', function (): void {
    $user = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Ahmed Ali',
        email: 'ahmed@example.test',
        password: 'Secret-12345',
    ));

    expect($user->email)->toBe('ahmed@example.test')
        ->and(app(AuthorizesActor::class)->allows($user->actor, AbilityName::TopUpsReview))->toBeFalse()
        ->and(app(AuthorizesActor::class)->allows($user->actor, AbilityName::RolesManage))->toBeFalse();
});

it('denies every ability by default for a customer', function (): void {
    $user = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Fatima Hassan',
        email: 'fatima@example.test',
        password: 'Secret-12345',
    ));

    foreach (AbilityName::cases() as $ability) {
        expect(app(AuthorizesActor::class)->allows($user->actor, $ability))->toBeFalse();
    }
});

it('stores password as a hash not plain text', function (): void {
    $user = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Omar Salih',
        email: 'omar@example.test',
        password: 'Secret-12345',
    ));

    $raw = DB::table('users')->where('id', $user->id)->value('password');
    expect($raw)->not->toBe('Secret-12345')
        ->and(str_starts_with($raw, '$'))->toBeTrue();
});
