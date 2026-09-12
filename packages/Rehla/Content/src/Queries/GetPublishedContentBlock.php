<?php

declare(strict_types=1);

namespace Rehla\Content\Queries;

use Rehla\Content\Data\LocalizedContentBlockView;
use Rehla\Content\Enums\ContentStatus;
use Rehla\Content\Models\ContentBlock;

final class GetPublishedContentBlock
{
    public function handle(string $key, string $locale = 'ar'): ?LocalizedContentBlockView
    {
        /** @var ContentBlock|null $block */
        $block = ContentBlock::where('key', $key)
            ->where('status', ContentStatus::Published)
            ->first();

        return $block?->toLocalizedView($locale);
    }
}
