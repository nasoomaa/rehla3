<?php

declare(strict_types=1);

namespace Rehla\Fulfillment\Queries;

use DateTimeImmutable;
use Rehla\Fulfillment\Data\CustomerActionRequestData;
use Rehla\Fulfillment\Exceptions\CustomerActionNotFoundException;
use Rehla\Fulfillment\Models\CustomerActionRequest;
use Rehla\Fulfillment\Models\ServiceExecution;

final class GetOwnedCustomerActionRequest
{
    /**
     * @return array{request: CustomerActionRequestData, orderId: string}
     */
    public function handle(string $accountId, string $actionRequestId): array
    {
        /** @var CustomerActionRequest|null $actionRequest */
        $actionRequest = CustomerActionRequest::query()->where('id', $actionRequestId)->first();

        if ($actionRequest === null) {
            throw CustomerActionNotFoundException::forId($actionRequestId);
        }

        /** @var ServiceExecution|null $execution */
        $execution = ServiceExecution::query()->where('id', $actionRequest->execution_id)->first();

        if ($execution === null || $execution->account_id !== $accountId) {
            throw CustomerActionNotFoundException::forId($actionRequestId);
        }

        return [
            'request' => new CustomerActionRequestData(
                id: (string) $actionRequest->id,
                executionId: (string) $actionRequest->execution_id,
                descriptionEn: (string) $actionRequest->description_en,
                descriptionAr: (string) $actionRequest->description_ar,
                requiredDocumentPurpose: $actionRequest->required_document_purpose ? (string) $actionRequest->required_document_purpose : null,
                status: (string) $actionRequest->status,
                dueAt: $actionRequest->due_at ? DateTimeImmutable::createFromInterface($actionRequest->due_at) : null,
                createdAt: DateTimeImmutable::createFromInterface($actionRequest->created_at),
                resolvedAt: $actionRequest->resolved_at ? DateTimeImmutable::createFromInterface($actionRequest->resolved_at) : null,
            ),
            'orderId' => (string) $execution->order_id,
        ];
    }
}
