<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BankAccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'bank_name_en' => (string) $this->bankNameEn,
            'bank_name_ar' => (string) $this->bankNameAr,
            'account_number' => (string) $this->accountNumber,
            'beneficiary_name' => (string) $this->beneficiaryName,
        ];
    }
}
