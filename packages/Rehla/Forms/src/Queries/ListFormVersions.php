<?php

declare(strict_types=1);

namespace Rehla\Forms\Queries;

use Rehla\Forms\Data\PublishedFormData;
use Rehla\Forms\Models\FormVersion;

final class ListFormVersions
{
    /**
     * @return list<PublishedFormData>
     */
    public function execute(): array
    {
        return FormVersion::orderBy('created_at', 'desc')
            ->get()
            ->map(fn (FormVersion $v): PublishedFormData => $v->toPublishedData())
            ->all();
    }
}
