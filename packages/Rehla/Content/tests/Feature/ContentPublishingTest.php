<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Content\Actions\CreateContentBlock;
use Rehla\Content\Actions\PublishContentBlock;
use Rehla\Content\Data\CreateContentBlockData;
use Rehla\Content\Enums\ContentStatus;
use Rehla\Content\Queries\GetPublishedContentBlock;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('sanitizes HTML content, publishes blocks, and returns localized content with fallback', function (): void {
    $createAction = app(CreateContentBlock::class);
    $publishAction = app(PublishContentBlock::class);
    $query = app(GetPublishedContentBlock::class);

    $actorId = (string) Str::uuid();
    $key = 'terms_and_conditions_'.Str::random(6);

    $data = new CreateContentBlockData(
        key: $key,
        titleEn: 'Terms and Conditions',
        titleAr: 'الشروط والأحكام',
        bodyEn: '<p>Welcome to Rehla. <script>alert("xss")</script><a href="javascript:bad()" onclick="exploit()">Safe Link</a></p>',
        bodyAr: '', // Empty Arabic body to test fallback
    );

    $block = $createAction->execute($data, $actorId);

    expect($block->status)->toBe(ContentStatus::Draft)
        ->and($block->bodyEn)->not->toContain('<script>')
        ->and($block->bodyEn)->not->toContain('javascript:')
        ->and($block->bodyEn)->not->toContain('onclick')
        ->and($block->bodyEn)->toContain('Welcome to Rehla.');

    // Publish
    $published = $publishAction->execute($block->id, $actorId);
    expect($published->status)->toBe(ContentStatus::Published)
        ->and($published->publishedAt)->not->toBeNull();

    // Query in Arabic: should get Arabic title, and fallback English body
    $viewAr = $query->handle($key, 'ar');
    expect($viewAr)->not->toBeNull()
        ->and($viewAr->locale)->toBe('ar')
        ->and($viewAr->direction)->toBe('rtl')
        ->and($viewAr->title)->toBe('الشروط والأحكام')
        ->and($viewAr->body)->toContain('Welcome to Rehla.');

    // Query in English
    $viewEn = $query->handle($key, 'en');
    expect($viewEn)->not->toBeNull()
        ->and($viewEn->locale)->toBe('en')
        ->and($viewEn->direction)->toBe('ltr')
        ->and($viewEn->title)->toBe('Terms and Conditions');
});
