<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Fulfillment\Actions\TransitionExecution;
use Rehla\Fulfillment\Data\TransitionExecutionData;
use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Fulfillment\Exceptions\InvalidExecutionTransitionException;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Identity\Enums\ActorType;
use Rehla\Purchasing\Contracts\ExecutionCreator;
use Rehla\Purchasing\Data\CreateExecutionData;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('creates fulfillment execution upon paid order and records history immutably', function (): void {
    $creator = app(ExecutionCreator::class);
    $orderId = (string) Str::uuid();
    $accountId = (string) Str::uuid();

    $execution = $creator->create(new CreateExecutionData(
        orderId: $orderId,
        accountId: $accountId,
        travelerId: (string) Str::uuid(),
        serviceId: (string) Str::uuid(),
        formVersionId: (string) Str::uuid(),
        submissionAnswers: ['field_1' => 'val_1'],
        documentIds: [(string) Str::uuid()],
    ));

    expect($execution->orderId)->toBe($orderId)
        ->and($execution->accountId)->toBe($accountId)
        ->and($execution->status)->toBe(ExecutionStatus::OrderReceived->value);

    // Initial status history was recorded
    $historyCount = DB::table('execution_status_history')
        ->where('execution_id', $execution->id)
        ->where('to_status', ExecutionStatus::OrderReceived->value)
        ->count();
    expect($historyCount)->toBe(1);

    // Status history is protected by trigger against UPDATE and DELETE
    expect(fn () => DB::table('execution_status_history')->where('execution_id', $execution->id)->update(['to_status' => 'tampered']))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('execution_status_history')->where('execution_id', $execution->id)->delete())
        ->toThrow(QueryException::class);
});

it('enforces fulfillment status transitions and rejects unauthorized transitions', function (): void {
    $creator = app(ExecutionCreator::class);
    $accountId = (string) Str::uuid();

    $execution = $creator->create(new CreateExecutionData(
        orderId: (string) Str::uuid(),
        accountId: $accountId,
        travelerId: (string) Str::uuid(),
        serviceId: (string) Str::uuid(),
        formVersionId: (string) Str::uuid(),
    ));

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        abilities: [AbilityName::ExecutionsManage->value],
    );

    $transitioner = app(TransitionExecution::class);

    // Valid transition: order_received -> under_review
    $updated = $transitioner->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::UnderReview,
        actor: $staffActor,
        reason: 'Staff started review',
    ));

    expect($updated->status)->toBe(ExecutionStatus::UnderReview->value);

    // Invalid transition: under_review -> completed (must go through in_processing first)
    expect(fn () => $transitioner->execute(new TransitionExecutionData(
        executionId: $execution->id,
        toStatus: ExecutionStatus::Completed,
        actor: $staffActor,
    )))->toThrow(InvalidExecutionTransitionException::class);
});
