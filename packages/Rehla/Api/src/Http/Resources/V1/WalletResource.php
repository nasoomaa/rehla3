<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WalletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'currency' => (string) $this->currency,
            'balance_minor' => (int) $this->minor,
            'balance_formatted' => number_format($this->minor / 100, 2),
        ];
    }
}
