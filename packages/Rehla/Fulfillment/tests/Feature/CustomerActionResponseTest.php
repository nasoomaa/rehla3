<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Fulfillment\Actions\RequestCustomerAction;
use Rehla\Fulfillment\Actions\RespondToCustomerAction;
use Rehla\Fulfillment\Actions\TransitionExecution;
use Rehla\Fulfillment\Data\RequestCustomerActionData;
use Rehla\Fulfillment\Data\RespondToCustomerActionData;
use Rehla\Fulfillment\Data\TransitionExecutionData;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Fulfillment\Exceptions\CustomerActionNotFoundException;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Identity\Enums\ActorType;
use Rehla\Purchasing\Contracts\ExecutionCreator;
use Rehla\Purchasing\Data\CreateExecutionData;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
    Storage::fake('private');
});

function createCleanDocumentForAction(string $ownerId): string
{
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $ownerId,
        purpose: DocumentPurpose::Passport,
    ));

    $validPdfContent = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('scan.pdf', $validPdfContent);
    $doc = app(StoreUpload::class)->handle($session->id, $file);
    $scanned = app(ScanDocument::class)->handle($doc->id);

    return $scanned->id;
}

it('lets only the owning account answer an open action request', function (): void {
    $ownerA = (string) Str::uuid();
    $ownerB = (string) Str::uuid();

    $execution = app(ExecutionCreator::class)->create(new CreateExecutionData(
        orderId: (string) Str::uuid(),
        accountId: $ownerA,
        travelerId: (string) Str::uuid(),
        serviceId: (string) Str::uuid(),
        formVersionId: (string) Str::uuid(),
    ));

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        abilities: [AbilityName::ExecutionsManage->value],
    );

    // Transition to under_review first so we can request action
    app(TransitionExecution::class)->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::UnderReview,
        actor: $staffActor,
    ));

    $actionRequest = app(RequestCustomerAction::class)->execute(new RequestCustomerActionData(
        executionId: $execution->id,
        actor: $staffActor,
        descriptionEn: 'Please provide updated address.',
        descriptionAr: 'يرجى تقديم العنوان المحدث.',
    ));

    $responder = app(RespondToCustomerAction::class);

    // Owner B cannot answer Owner A's request
    expect(fn () => $responder->execute(new RespondToCustomerActionData(
        actionRequestId: $actionRequest->id,
        accountId: $ownerB,
        message: 'Trying to answer for someone else',
    )))->toThrow(CustomerActionNotFoundException::class);

    // Owner A can answer
    $response = $responder->execute(new RespondToCustomerActionData(
        actionRequestId: $actionRequest->id,
        accountId: $ownerA,
        message: 'My updated address is Khartoum 2',
    ));

    expect($response->actionRequestId)->toBe($actionRequest->id)
        ->and($response->message)->toBe('My updated address is Khartoum 2');

    // Execution status transitioned to requested_action_received
    $execRow = DB::table('service_executions')->where('id', $execution->id)->first();
    expect($execRow->status)->toBe(ExecutionStatus::RequestedActionReceived->value);
});

it('attaches clean document when customer action requires a document', function (): void {
    $ownerA = (string) Str::uuid();

    $execution = app(ExecutionCreator::class)->create(new CreateExecutionData(
        orderId: (string) Str::uuid(),
        accountId: $ownerA,
        travelerId: (string) Str::uuid(),
        serviceId: (string) Str::uuid(),
        formVersionId: (string) Str::uuid(),
    ));

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        abilities: [AbilityName::ExecutionsManage->value],
    );

    app(TransitionExecution::class)->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::UnderReview,
        actor: $staffActor,
    ));

    $actionRequest = app(RequestCustomerAction::class)->execute(new RequestCustomerActionData(
        executionId: $execution->id,
        actor: $staffActor,
        descriptionEn: 'Please upload passport copy.',
        descriptionAr: 'يرجى رفع نسخة من جواز السفر.',
        requiredDocumentPurpose: DocumentPurpose::Passport->value,
    ));

    $cleanDocId = createCleanDocumentForAction($ownerA);

    $response = app(RespondToCustomerAction::class)->execute(new RespondToCustomerActionData(
        actionRequestId: $actionRequest->id,
        accountId: $ownerA,
        message: 'Attached is the clear scan.',
        documentId: $cleanDocId,
    ));

    expect($response->documentId)->toBe($cleanDocId);

    // Document was attached to execution_documents
    $docAttached = DB::table('execution_documents')
        ->where('execution_id', $execution->id)
        ->where('document_id', $cleanDocId)
        ->exists();
    expect($docAttached)->toBeTrue();

    // Document status transitioned to attached
    $docStatus = DB::table('documents')->where('id', $cleanDocId)->value('status');
    expect($docStatus)->toBe('attached');

    // Replay returns same response without duplicate response records
    $replayed = app(RespondToCustomerAction::class)->execute(new RespondToCustomerActionData(
        actionRequestId: $actionRequest->id,
        accountId: $ownerA,
        message: 'Attached is the clear scan.',
        documentId: $cleanDocId,
    ));
    expect($replayed->id)->toBe($response->id);
    expect(DB::table('customer_action_responses')->where('action_request_id', $actionRequest->id)->count())->toBe(1);
});

it('rejects response without required document', function (): void {
    $ownerA = (string) Str::uuid();

    $execution = app(ExecutionCreator::class)->create(new CreateExecutionData(
        orderId: (string) Str::uuid(),
        accountId: $ownerA,
        travelerId: (string) Str::uuid(),
        serviceId: (string) Str::uuid(),
        formVersionId: (string) Str::uuid(),
    ));

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        abilities: [AbilityName::ExecutionsManage->value],
    );

    app(TransitionExecution::class)->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::UnderReview,
        actor: $staffActor,
    ));

    $actionRequest = app(RequestCustomerAction::class)->execute(new RequestCustomerActionData(
        executionId: $execution->id,
        actor: $staffActor,
        descriptionEn: 'Please upload document.',
        descriptionAr: 'يرجى رفع المستند.',
        requiredDocumentPurpose: DocumentPurpose::Passport->value,
    ));

    expect(fn () => app(RespondToCustomerAction::class)->execute(new RespondToCustomerActionData(
        actionRequestId: $actionRequest->id,
        accountId: $ownerA,
        message: 'No document attached',
        documentId: null,
    )))->toThrow(InvalidArgumentException::class);
});
