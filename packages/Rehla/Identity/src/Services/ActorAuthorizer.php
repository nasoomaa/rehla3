<?php

declare(strict_types=1);

namespace Rehla\Identity\Services;

use Carbon\CarbonImmutable;
use Rehla\Identity\Contracts\AuthorizesActor;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Data\ResourceRef;
use Rehla\Identity\Enums\AbilityName;

final class ActorAuthorizer implements AuthorizesActor
{
    public function allows(ActorData $actor, AbilityName $ability, ?ResourceRef $resource = null): bool
    {
        // 1. Deny by default: actor must explicitly possess the ability
        if (! in_array($ability->value, $actor->abilities, true)) {
            return false;
        }

        // 2. Sensitive abilities mandate recent MFA confirmation within 12-hour window
        if ($ability->requiresMfa()) {
            if ($actor->mfaConfirmedAt === null) {
                return false;
            }

            $confirmedAt = CarbonImmutable::parse($actor->mfaConfirmedAt);
            if ($confirmedAt->isBefore(CarbonImmutable::now()->subHours(12))) {
                return false;
            }
        }

        return true;
    }
}
