<?php

declare(strict_types=1);

namespace Rehla\Content\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Content\Data\ContentBlockData;
use Rehla\Content\Data\CreateContentBlockData;
use Rehla\Content\Models\ContentBlock;
use Rehla\Content\Support\HtmlSanitizer;

final class UpdateContentBlock
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(string $blockId, CreateContentBlockData $data, ?string $actorId = null, string $actorType = 'staff'): ContentBlockData
    {
        return DB::transaction(function () use ($blockId, $data, $actorId, $actorType): ContentBlockData {
            /** @var ContentBlock|null $block */
            $block = ContentBlock::lockForUpdate()->find($blockId);

            if (! $block) {
                throw new DomainException("Content block not found: {$blockId}");
            }

            $block->update([
                'key' => $data->key,
                'title_en' => $data->titleEn,
                'title_ar' => $data->titleAr,
                'body_en' => HtmlSanitizer::clean($data->bodyEn),
                'body_ar' => HtmlSanitizer::clean($data->bodyAr),
                'updated_by' => $actorId,
            ]);

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'content_block.updated',
                    subjectType: 'content_block',
                    subjectId: $blockId,
                    metadata: ['key' => $data->key],
                ));
            }

            return $block->toData();
        });
    }
}
