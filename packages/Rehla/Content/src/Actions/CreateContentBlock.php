<?php

declare(strict_types=1);

namespace Rehla\Content\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Audit\Contracts\AuditWriter;
use Rehla\Audit\Data\AppendAuditData;
use Rehla\Content\Data\ContentBlockData;
use Rehla\Content\Data\CreateContentBlockData;
use Rehla\Content\Enums\ContentStatus;
use Rehla\Content\Models\ContentBlock;
use Rehla\Content\Support\HtmlSanitizer;

final class CreateContentBlock
{
    public function __construct(
        private readonly ?AuditWriter $auditWriter = null,
    ) {}

    public function execute(CreateContentBlockData $data, ?string $actorId = null, string $actorType = 'staff'): ContentBlockData
    {
        return DB::transaction(function () use ($data, $actorId, $actorType): ContentBlockData {
            $id = (string) Str::uuid();

            $block = ContentBlock::create([
                'id' => $id,
                'key' => $data->key,
                'title_en' => $data->titleEn,
                'title_ar' => $data->titleAr,
                'body_en' => HtmlSanitizer::clean($data->bodyEn),
                'body_ar' => HtmlSanitizer::clean($data->bodyAr),
                'status' => ContentStatus::Draft,
                'created_by' => $actorId,
            ]);

            if ($this->auditWriter !== null && $actorId !== null) {
                $this->auditWriter->append(new AppendAuditData(
                    actorType: $actorType,
                    actorId: $actorId,
                    action: 'content_block.created',
                    subjectType: 'content_block',
                    subjectId: $id,
                    metadata: ['key' => $data->key],
                ));
            }

            return $block->toData();
        });
    }
}
