<?php

declare(strict_types=1);

namespace Rehla\Identity\Enums;

enum AbilityName: string
{
    case ServicesManage = 'services.manage';
    case FormsManage = 'forms.manage';
    case CustomersView = 'customers.view';
    case TravelersView = 'travelers.view';
    case WalletsView = 'wallets.view';
    case BankAccountsManage = 'bank_accounts.manage';
    case TopUpsReview = 'topups.review';
    case OrdersView = 'orders.view';
    case ExecutionsManage = 'executions.manage';
    case DocumentsView = 'documents.view';
    case ContentManage = 'content.manage';
    case NotificationsManage = 'notifications.manage';
    case RolesManage = 'roles.manage';
    case AuditView = 'audit.view';
    case ReportingView = 'reporting.view';

    /** Abilities that require a fresh MFA confirmation (within 12 hours). */
    public function requiresMfa(): bool
    {
        return match ($this) {
            self::TopUpsReview, self::RolesManage, self::AuditView => true,
            default => false,
        };
    }
}
