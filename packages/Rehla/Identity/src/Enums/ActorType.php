<?php

declare(strict_types=1);

namespace Rehla\Identity\Enums;

enum ActorType: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case System = 'system';
}
