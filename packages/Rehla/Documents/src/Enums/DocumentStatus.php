<?php

declare(strict_types=1);

namespace Rehla\Documents\Enums;

enum DocumentStatus: string
{
    case PendingScan = 'pending_scan';
    case Quarantined = 'quarantined';
    case Clean = 'clean';
    case Rejected = 'rejected';
    case Attached = 'attached';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::PendingScan => $target === self::Quarantined || $target === self::Clean || $target === self::Rejected,
            self::Quarantined => $target === self::Clean || $target === self::Rejected,
            self::Clean => $target === self::Attached,
            self::Rejected => false,
            self::Attached => false,
        };
    }
}
