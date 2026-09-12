<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TopUpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'amount_minor' => (int) $this->amountMinor,
            'currency' => 'SDG',
            'transaction_reference' => (string) $this->transactionReference,
            'submitted_at' => $this->submittedAt instanceof \DateTimeInterface ? $this->submittedAt->format(DATE_ATOM) : (string) $this->submittedAt,
        ];
    }
}
