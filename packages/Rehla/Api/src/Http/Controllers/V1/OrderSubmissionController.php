<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rehla\Api\Errors\ProblemDetailsFactory;
use Rehla\Purchasing\Actions\SubmitOrder;
use Rehla\Purchasing\Data\SubmitOrderData;
use Rehla\Purchasing\Exceptions\IdempotencyKeyReusedException;
use Rehla\Purchasing\Exceptions\PriceChangedException;
use Rehla\Wallet\Exceptions\InsufficientWalletBalanceException;

final class OrderSubmissionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if (empty($idempotencyKey)) {
            return ProblemDetailsFactory::make(
                status: 422,
                code: 'MISSING_IDEMPOTENCY_KEY',
                detail: __('The Idempotency-Key header is required for order submissions.'),
            );
        }

        $validated = $request->validate([
            'service_id' => ['required', 'string'],
            'traveler_id' => ['required', 'string'],
            'accepted_price_minor' => ['required', 'integer', 'min:1'],
            'accepted_price_version' => ['required', 'integer'],
            'form_version_id' => ['required', 'string'],
            'answers' => ['nullable', 'array'],
            'document_ids' => ['nullable', 'array'],
        ]);

        try {
            $result = app(SubmitOrder::class)->execute(new SubmitOrderData(
                accountId: $request->user()->id,
                serviceId: $validated['service_id'],
                travelerId: $validated['traveler_id'],
                acceptedPriceMinor: $validated['accepted_price_minor'],
                acceptedPriceVersion: $validated['accepted_price_version'],
                formVersionId: $validated['form_version_id'],
                idempotencyKey: (string) $idempotencyKey,
                answers: $validated['answers'] ?? [],
                documentIds: $validated['document_ids'] ?? [],
            ));

            $isReplay = (bool) ($result->metadata['is_replay'] ?? false);
            $statusCode = $isReplay ? 200 : 201;

            $response = response()->json([
                'data' => [
                    'order_id' => $result->orderId,
                    'execution_id' => $result->executionId,
                    'status' => $result->status,
                    'amount_paid_minor' => $result->amountPaidMinor,
                    'currency' => $result->currency,
                ],
            ], $statusCode);

            if ($isReplay) {
                $response->header('Idempotency-Replayed', 'true');
            }

            return $response;
        } catch (IdempotencyKeyReusedException $e) {
            return ProblemDetailsFactory::make(
                status: 409,
                code: 'IDEMPOTENCY_CONFLICT',
                detail: __('Idempotency key has already been used with differing parameters.'),
            );
        } catch (PriceChangedException $e) {
            return ProblemDetailsFactory::make(
                status: 409,
                code: 'PRICE_CHANGED',
                detail: __('The service price has changed since quote was retrieved.'),
            );
        } catch (InsufficientWalletBalanceException $e) {
            return ProblemDetailsFactory::make(
                status: 422,
                code: 'INSUFFICIENT_FUNDS',
                detail: __('Insufficient wallet balance.'),
            );
        }
    }
}
