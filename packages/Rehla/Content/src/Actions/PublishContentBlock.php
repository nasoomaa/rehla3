<?php

declare(strict_types=1);

namespace Rehla\Content\Actions;

use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Content\Data\ContentBlockData;
use Rehla\Content\Enums\ContentStatus;
use Rehla\Content\Models\ContentBlock;

final class PublishContentBlock
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $blockId, ?string $actorId = null, string $actorType = 'staff'): ContentBlockData
    {
        return DB::transaction(function () use ($blockId, $actorId, $actorType): ContentBlockData {
            /** @var ContentBlock|null $block */
            $block = ContentBlock::lockForUpdate()->find($blockId);

            if (! $block) {
                throw new DomainException("Content block not found: {$blockId}");
            }

            $now = new DateTimeImmutable;
            $block->status = ContentStatus::Published;
            $block->published_at = $now;
            $block->updated_by = $actorId;
            $block->save();

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'content_block.published',
                    subjectType: 'content_block',
                    subjectId: $blockId,
                    metadata: [
                        'key' => (string) $block->key,
                        'published_at' => $now->format(DATE_ATOM),
                    ],
                ));
            }

            return $block->toData();
        });
    }
}
