<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Content\Actions\CreateContentBlock;
use Rehla\Content\Actions\PublishContentBlock;
use Rehla\Content\Actions\UpdateContentBlock;
use Rehla\Content\Data\CreateContentBlockData;
use Rehla\Content\Queries\GetPublishedContentBlock;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('retrieves localized content blocks and audits lifecycle updates', function (): void {
    $createAction = app(CreateContentBlock::class);
    $updateAction = app(UpdateContentBlock::class);
    $publishAction = app(PublishContentBlock::class);
    $query = app(GetPublishedContentBlock::class);

    $actorId = (string) Str::uuid();
    $key = 'faq_refund_'.Str::random(6);

    $block = $createAction->execute(new CreateContentBlockData(
        key: $key,
        titleEn: 'Refund Policy',
        titleAr: 'سياسة الاسترداد',
        bodyEn: 'Refunds processed within 48 hours.',
        bodyAr: 'تتم معالجة الاسترداد خلال 48 ساعة.',
    ), $actorId);

    $updateAction->execute($block->id, new CreateContentBlockData(
        key: $key,
        titleEn: 'Refund Policy Updated',
        titleAr: 'سياسة الاسترداد المحدثة',
        bodyEn: 'Refunds processed within 24 hours.',
        bodyAr: 'تتم معالجة الاسترداد خلال 24 ساعة.',
    ), $actorId);

    $publishAction->execute($block->id, $actorId);

    $publishedBlock = $query->handle($key, 'ar');
    expect($publishedBlock)->not->toBeNull()
        ->and($publishedBlock->title)->toBe('سياسة الاسترداد المحدثة')
        ->and($publishedBlock->body)->toBe('تتم معالجة الاسترداد خلال 24 ساعة.');

    // Check audit entries
    $auditLogs = DB::table('audit_entries')
        ->where('subject_type', 'content_block')
        ->where('subject_id', $block->id)
        ->pluck('action')
        ->all();

    expect($auditLogs)->toContain('content_block.created')
        ->and($auditLogs)->toContain('content_block.updated')
        ->and($auditLogs)->toContain('content_block.published');
});
