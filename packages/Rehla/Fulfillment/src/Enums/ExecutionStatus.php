<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Enums;

enum ExecutionStatus: string
{
    case OrderReceived = 'order_received';
    case UnderReview = 'under_review';
    case InProcessing = 'in_processing';
    case CustomerActionRequired = 'customer_action_required';
    case RequestedActionReceived = 'requested_action_received';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Cancelled => true,
            default => false,
        };
    }
}
