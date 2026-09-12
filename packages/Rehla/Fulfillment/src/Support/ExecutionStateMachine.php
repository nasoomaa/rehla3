<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Support;

use Rehla\Fulfillment\Enums\ExecutionStatus;

final class ExecutionStateMachine
{
    /**
     * @var array<string, list<string>>
     */
    private const array ALLOWED_TRANSITIONS = [
        'order_received' => ['under_review', 'cancelled'],
        'under_review' => ['in_processing', 'customer_action_required', 'cancelled'],
        'in_processing' => ['customer_action_required', 'completed', 'cancelled'],
        'customer_action_required' => ['requested_action_received', 'cancelled'],
        'requested_action_received' => ['in_processing', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public static function allows(ExecutionStatus $from, ExecutionStatus $to): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }
}
