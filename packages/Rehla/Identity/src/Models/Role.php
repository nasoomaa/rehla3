<?php

declare(strict_types=1);

namespace Rehla\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasUuids;

    protected $table = 'roles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'label',
    ];

    public function abilities(): BelongsToMany
    {
        return $this->belongsToMany(Ability::class, 'role_ability', 'role_id', 'ability_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role', 'role_id', 'user_id');
    }
}
