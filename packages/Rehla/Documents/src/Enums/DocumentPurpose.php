<?php

declare(strict_types=1);

namespace Rehla\Documents\Enums;

enum DocumentPurpose: string
{
    case Passport = 'passport';
    case NationalId = 'national_id';
    case BankReceipt = 'bank_receipt';
    case VisaPhoto = 'visa_photo';
    case Other = 'other';
}
