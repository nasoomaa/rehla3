<?php

declare(strict_types=1);

namespace Rehla\Identity\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Identity\Data\UserData;
use Rehla\Identity\Enums\AccountStatus;
use Rehla\Identity\Models\User;

final class RegisterCustomer
{
    public function handle(RegisterCustomerData $data): UserData
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => $data->name,
            'email' => strtolower(trim($data->email)),
            'password' => Hash::make($data->password),
            'status' => AccountStatus::Active->value,
        ]);

        return $user->toUserData();
    }
}
