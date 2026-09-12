<?php

declare(strict_types=1);

use Rehla\Fulfillment\Enums\ExecutionStatus;
use Rehla\Fulfillment\Support\ExecutionStateMachine;

dataset('allowed execution transitions', [
    ['order_received', 'under_review'],
    ['order_received', 'cancelled'],
    ['under_review', 'in_processing'],
    ['under_review', 'customer_action_required'],
    ['under_review', 'cancelled'],
    ['in_processing', 'customer_action_required'],
    ['in_processing', 'completed'],
    ['in_processing', 'cancelled'],
    ['customer_action_required', 'requested_action_received'],
    ['customer_action_required', 'cancelled'],
    ['requested_action_received', 'in_processing'],
    ['requested_action_received', 'cancelled'],
]);

it('allows only the declared transition graph', function (string $from, string $to): void {
    expect(ExecutionStateMachine::allows(ExecutionStatus::from($from), ExecutionStatus::from($to)))->toBeTrue();
})->with('allowed execution transitions');

it('disallows transitions not in the graph', function (): void {
    expect(ExecutionStateMachine::allows(ExecutionStatus::OrderReceived, ExecutionStatus::Completed))->toBeFalse()
        ->and(ExecutionStateMachine::allows(ExecutionStatus::Completed, ExecutionStatus::InProcessing))->toBeFalse()
        ->and(ExecutionStateMachine::allows(ExecutionStatus::Cancelled, ExecutionStatus::InProcessing))->toBeFalse();
});
