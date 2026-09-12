<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Enums;

enum PurchaseAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
}
