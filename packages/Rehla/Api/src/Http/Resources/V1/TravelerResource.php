<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TravelerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'full_name' => (string) $this->fullName,
            'date_of_birth' => (string) $this->dateOfBirth,
            'gender' => $this->gender instanceof \BackedEnum ? $this->gender->value : (string) $this->gender,
            'passport_number' => (string) $this->passportNumber,
            'passport_issued_at' => (string) ($this->passportIssuedAt ?? ''),
            'passport_expires_at' => (string) ($this->passportExpiresAt ?? ''),
        ];
    }
}
