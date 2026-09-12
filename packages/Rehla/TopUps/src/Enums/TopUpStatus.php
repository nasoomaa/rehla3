<?php

declare(strict_types=1);

namespace Rehla\TopUps\Enums;

enum TopUpStatus: string
{
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
