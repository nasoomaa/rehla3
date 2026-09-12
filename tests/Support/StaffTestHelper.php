<?php

declare(strict_types=1);

namespace Tests\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;

final class StaffTestHelper
{
    /**
     * @param  list<string>  $abilities
     * @return array{id: string, name: string, email: string, password: string}
     */
    public static function createStaff(
        array $abilities = [],
        ?CarbonInterface $mfaConfirmedAt = null,
        string $password = 'StaffPass123!',
        string $name = 'Staff Member'
    ): array {
        $email = 'staff_'.Str::random(8).'@example.test';

        $userData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
            name: $name,
            email: $email,
            password: $password,
        ));

        // Create staff profile
        DB::table('staff_profiles')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userData->id,
            'mfa_confirmed_at' => $mfaConfirmedAt?->toDateTimeString() ?? CarbonImmutable::now()->toDateTimeString(),
            'created_at' => CarbonImmutable::now()->toDateTimeString(),
            'updated_at' => CarbonImmutable::now()->toDateTimeString(),
        ]);

        if (! empty($abilities)) {
            $roleId = (string) Str::uuid();
            $roleName = 'role_'.Str::random(6);

            DB::table('roles')->insert([
                'id' => $roleId,
                'name' => $roleName,
                'label' => 'Custom Role',
                'created_at' => CarbonImmutable::now()->toDateTimeString(),
                'updated_at' => CarbonImmutable::now()->toDateTimeString(),
            ]);

            DB::table('user_role')->insert([
                'user_id' => $userData->id,
                'role_id' => $roleId,
            ]);

            foreach ($abilities as $abilityName) {
                $existingAbility = DB::table('abilities')->where('name', $abilityName)->first();
                if ($existingAbility === null) {
                    $abilityId = (string) Str::uuid();
                    DB::table('abilities')->insert([
                        'id' => $abilityId,
                        'name' => $abilityName,
                        'created_at' => CarbonImmutable::now()->toDateTimeString(),
                        'updated_at' => CarbonImmutable::now()->toDateTimeString(),
                    ]);
                } else {
                    $abilityId = $existingAbility->id;
                }

                DB::table('role_ability')->insertOrIgnore([
                    'role_id' => $roleId,
                    'ability_id' => $abilityId,
                ]);
            }
        }

        return [
            'id' => $userData->id,
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * @param  array{id: string, name: string, email: string, password: string}  $staff
     */
    public static function loginStaff(array $staff): void
    {
        $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);
        if ($user === null) {
            throw new \RuntimeException("Staff user {$staff['id']} not found by admin provider");
        }

        Auth::guard('admin')->setUser($user);
    }
}
