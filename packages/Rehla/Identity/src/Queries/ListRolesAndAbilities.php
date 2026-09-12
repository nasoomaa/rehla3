<?php

declare(strict_types=1);

namespace Rehla\Identity\Queries;

use Rehla\Identity\Data\RoleData;
use Rehla\Identity\Models\Role;

final class ListRolesAndAbilities
{
    /**
     * @return list<RoleData>
     */
    public function execute(): array
    {
        return Role::with('abilities')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn (Role $r): RoleData => new RoleData(
                id: (string) $r->id,
                name: (string) $r->name,
                label: $r->label ? (string) $r->label : null,
                abilities: $r->abilities->pluck('name')->all(),
            ))
            ->all();
    }
}
