<?php

declare(strict_types=1);

namespace Rehla\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Data\UserData;

class User extends Authenticatable
{
    use HasUuids, Notifiable;

    protected $table = 'users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class, 'user_id', 'id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id');
    }

    public function toActorData(): ActorData
    {
        $this->loadMissing(['staffProfile', 'roles.abilities']);

        $isStaff = $this->staffProfile !== null;
        $mfa = $this->staffProfile?->mfa_confirmed_at?->toIso8601String();

        /** @var list<string> $abilities */
        $abilities = $this->roles
            ->flatMap(fn (Role $role) => $role->abilities->pluck('name'))
            ->unique()
            ->values()
            ->all();

        return new ActorData(
            id: (string) $this->id,
            type: $isStaff ? 'staff' : 'customer',
            mfaConfirmedAt: $mfa,
            abilities: $abilities,
        );
    }

    public function toUserData(): UserData
    {
        return new UserData(
            id: (string) $this->id,
            name: (string) $this->name,
            email: (string) $this->email,
            status: (string) $this->status,
            actor: $this->toActorData(),
        );
    }
}
