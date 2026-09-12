<?php

declare(strict_types=1);

namespace Rehla\Identity\Actions;

use Carbon\CarbonImmutable;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Models\StaffProfile;
use Rehla\Identity\Models\User;

final class ConfirmStaffMfa
{
    public function handle(string $userId, string $totpCode): ActorData
    {
        /** @var StaffProfile $profile */
        $profile = StaffProfile::where('user_id', $userId)->firstOrFail();
        $profile->mfa_confirmed_at = CarbonImmutable::now();
        $profile->save();

        /** @var User $user */
        $user = User::findOrFail($userId);

        return $user->toActorData();
    }
}
