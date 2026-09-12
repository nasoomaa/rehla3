<?php

declare(strict_types=1);

namespace Rehla\Travelers\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Data\TravelerSnapshot;
use Rehla\Travelers\Enums\Gender;

class Traveler extends Model
{
    use HasUuids;

    protected $table = 'travelers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'owner_id',
        'full_name',
        'date_of_birth',
        'gender',
        'passport_number',
        'normalized_passport_number',
        'passport_issued_at',
        'passport_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'gender' => Gender::class,
            'passport_issued_at' => 'date:Y-m-d',
            'passport_expires_at' => 'date:Y-m-d',
        ];
    }

    public function toData(): TravelerData
    {
        return new TravelerData(
            ownerId: (string) $this->owner_id,
            fullName: (string) $this->full_name,
            dateOfBirth: $this->date_of_birth->format('Y-m-d'),
            gender: $this->gender,
            passportNumber: (string) $this->passport_number,
            passportIssuedAt: $this->passport_issued_at->format('Y-m-d'),
            passportExpiresAt: $this->passport_expires_at->format('Y-m-d'),
            id: (string) $this->id,
            normalizedPassportNumber: (string) $this->normalized_passport_number,
        );
    }

    public function toSnapshot(): TravelerSnapshot
    {
        return new TravelerSnapshot(
            id: (string) $this->id,
            ownerId: (string) $this->owner_id,
            fullName: (string) $this->full_name,
            dateOfBirth: $this->date_of_birth->format('Y-m-d'),
            gender: $this->gender,
            passportNumber: (string) $this->normalized_passport_number,
            passportIssuedAt: $this->passport_issued_at->format('Y-m-d'),
            passportExpiresAt: $this->passport_expires_at->format('Y-m-d'),
        );
    }
}
