<?php

declare(strict_types=1);

namespace Rehla\Forms\Queries;

use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Enums\FormVersionStatus;
use Rehla\Forms\Models\FormVersion;

final class GetPublishedForm
{
    public function handle(string $serviceId): ?PublishedFormData
    {
        /** @var FormVersion|null $version */
        $version = FormVersion::where('service_id', $serviceId)
            ->where('status', FormVersionStatus::Published)
            ->orderByDesc('version')
            ->first();

        return $version?->toPublishedData();
    }
}
