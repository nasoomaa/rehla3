<?php

declare(strict_types=1);

namespace Rehla\Api\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Enums\FieldType;
use Rehla\Identity\Actions\IssueCustomerToken;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Wallet\Actions\OpenWallet;

function setupMutationCustomer(int $balanceMinor = 100_000_00): array
{
    $email = 'api_mut_'.Str::random(6).'@example.com';
    $user = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Mutation User',
        email: $email,
        password: 'Password123!',
    ));

    $wallet = app(OpenWallet::class)->execute($user->id);
    if ($balanceMinor > 0) {
        DB::table('wallets')->where('id', $wallet->id)->update(['balance_minor' => $balanceMinor]);
    }

    $token = app(IssueCustomerToken::class)->handle($user->id, 'mutation-test');

    return [$user, $token, $wallet];
}

function setupMutationServiceWithForm(int $priceMinor = 50_000_00): array
{
    $service = app(CreateService::class)->execute(new CreateServiceData(
        slug: 'api-service-'.Str::random(6),
        nameEn: 'API Service',
        nameAr: 'خدمة واجهة',
        shortDescriptionEn: 'Short',
        shortDescriptionAr: 'موجز',
        detailedDescriptionEn: 'Detailed',
        detailedDescriptionAr: 'مفصل',
        expectedDurationEn: '1 day',
        expectedDurationAr: 'يوم',
        priceMinor: $priceMinor,
        requirements: [
            ['text_en' => 'Passport', 'text_ar' => 'جواز', 'sort_order' => 1],
        ],
        media: [
            ['document_id' => (string) Str::uuid(), 'alt_en' => 'Pic', 'alt_ar' => 'صورة', 'sort_order' => 1],
        ],
    ), actorId: (string) Str::uuid());

    $publishedService = app(PublishService::class)->execute($service->id, actorId: (string) Str::uuid());

    $draft = app(CreateFormDraft::class)->execute($publishedService->id, [
        new FormFieldData(
            key: 'visit_reason',
            type: FieldType::ShortText,
            labelEn: 'Visit Reason',
            labelAr: 'سبب الزيارة',
            required: true,
        ),
    ], actorId: (string) Str::uuid());

    $publishedForm = app(PublishFormVersion::class)->execute($draft->id, actorId: (string) Str::uuid());

    return [$publishedService, $publishedForm];
}

it('requires Idempotency-Key header on order submissions', function (): void {
    [$user, $token] = setupMutationCustomer();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/order-submissions', []);

    $response->assertStatus(422)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('code', 'MISSING_IDEMPOTENCY_KEY');
});

it('creates an order, and replays idempotently on identical request', function (): void {
    [$user, $token] = setupMutationCustomer(balanceMinor: 100_000_00);
    [$service, $form] = setupMutationServiceWithForm(priceMinor: 50_000_00);

    $traveler = app(CreateTraveler::class)->handle(new TravelerData(
        ownerId: $user->id,
        fullName: 'API Traveler',
        dateOfBirth: '1992-02-02',
        gender: Gender::Female,
        passportNumber: 'P'.Str::random(7),
        passportIssuedAt: '2020-01-01',
        passportExpiresAt: '2032-01-01',
    ));

    $idempotencyKey = (string) Str::uuid();

    $payload = [
        'service_id' => $service->id,
        'traveler_id' => $traveler->id,
        'accepted_price_minor' => 50_000_00,
        'accepted_price_version' => 1,
        'form_version_id' => $form->id,
        'answers' => [
            'visit_reason' => 'Business Meeting',
        ],
    ];

    // First submission -> 201 Created
    $firstResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/order-submissions', $payload);

    $firstResponse->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['order_id', 'execution_id', 'status', 'amount_paid_minor', 'currency'],
        ]);

    // Replay identical submission -> 200 OK with Idempotency-Replayed: true
    $replayResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/order-submissions', $payload);

    $replayResponse->assertOk()
        ->assertHeader('Idempotency-Replayed', 'true')
        ->assertJsonPath('data.order_id', $firstResponse->json('data.order_id'));

    // Replay with different payload -> 409 Conflict
    $conflictPayload = array_merge($payload, ['answers' => ['visit_reason' => 'Tourism']]);
    $conflictResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/order-submissions', $conflictPayload);

    $conflictResponse->assertStatus(409)
        ->assertHeader('Content-Type', 'application/problem+json');
});

it('handles file upload and enforces allowed types and size', function (): void {
    Storage::fake('private');
    [$user, $token] = setupMutationCustomer();

    $file = UploadedFile::fake()->image('document.jpg');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/uploads', [
            'file' => $file,
            'purpose' => 'bank_receipt',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['document_id', 'status'],
        ]);
});

it('submits top-up request with clean receipt document', function (): void {
    Storage::fake('private');
    [$user, $token] = setupMutationCustomer();

    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Omdurman National Bank',
        bankNameAr: 'بنك أم درمان الوطني',
        beneficiaryName: 'Rehla Co',
        accountNumber: 'ONB-'.Str::random(6),
    ));

    // Upload receipt first
    $upload = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/uploads', [
            'file' => UploadedFile::fake()->image('receipt.png'),
            'purpose' => 'bank_receipt',
        ]);

    $documentId = $upload->json('data.document_id');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/top-ups', [
            'bank_account_id' => $bank->id,
            'amount_minor' => 500_000,
            'transaction_reference' => 'REF-'.Str::random(6),
            'receipt_document_id' => $documentId,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'status', 'amount_minor'],
        ]);
});
