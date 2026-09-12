<?php

declare(strict_types=1);

namespace Rehla\Content\Queries;

use Rehla\Content\Data\ContentBlockData;
use Rehla\Content\Models\ContentBlock;

final class ListContentBlocks
{
    /**
     * @return list<ContentBlockData>
     */
    public function execute(): array
    {
        return ContentBlock::orderBy('key', 'asc')
            ->get()
            ->map(fn (ContentBlock $c): ContentBlockData => $c->toData())
            ->all();
    }
}
