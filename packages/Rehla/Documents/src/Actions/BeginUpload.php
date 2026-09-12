<?php

declare(strict_types=1);

namespace Rehla\Documents\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Models\UploadSession;

final class BeginUpload
{
    public function handle(BeginUploadData $data): UploadSession
    {
        return UploadSession::create([
            'id' => (string) Str::uuid(),
            'owner_id' => $data->ownerId,
            'purpose' => $data->purpose,
            'expires_at' => CarbonImmutable::now()->addMinutes($data->lifetimeMinutes),
        ]);
    }
}
