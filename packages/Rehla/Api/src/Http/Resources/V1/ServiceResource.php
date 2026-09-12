<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'slug' => (string) $this->slug,
            'name_en' => (string) $this->nameEn,
            'name_ar' => (string) $this->nameAr,
            'short_description_en' => (string) $this->shortDescriptionEn,
            'short_description_ar' => (string) $this->shortDescriptionAr,
            'price_minor' => (int) $this->currentPriceMinor,
            'currency' => (string) $this->currency,
        ];
    }
}
