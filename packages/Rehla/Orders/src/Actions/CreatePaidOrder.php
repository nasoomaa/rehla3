<?php

declare(strict_types=1);

namespace Rehla\Orders\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Orders\Contracts\OrderWriter;
use Rehla\Orders\Data\CreatePaidOrderData;
use Rehla\Orders\Data\PaidOrderData;
use Rehla\Orders\Exceptions\InvalidOrderDataException;
use Rehla\Orders\Models\Order;
use Rehla\Orders\Models\OrderFormSnapshot;
use Rehla\Orders\Models\OrderServiceSnapshot;
use Rehla\Orders\Models\OrderTravelerSnapshot;

final class CreatePaidOrder implements OrderWriter
{
    public function createPaid(CreatePaidOrderData $data): PaidOrderData
    {
        if ($data->currency !== 'SDG') {
            throw InvalidOrderDataException::unsupportedCurrency($data->currency);
        }

        if ($data->priceMinor <= 0 || $data->priceMinor !== $data->amountPaidMinor) {
            throw InvalidOrderDataException::invalidPriceOrPayment();
        }

        return DB::transaction(function () use ($data): PaidOrderData {
            $orderId = (string) Str::uuid();
            $now = CarbonImmutable::now();

            $order = Order::create([
                'id' => $orderId,
                'account_id' => $data->accountId,
                'service_id' => $data->serviceId,
                'traveler_id' => $data->travelerId,
                'price_minor' => $data->priceMinor,
                'amount_paid_minor' => $data->amountPaidMinor,
                'currency' => $data->currency,
                'debit_ledger_entry_id' => $data->debitLedgerEntryId,
                'financial_status' => 'paid',
                'created_at' => $now,
            ]);

            OrderServiceSnapshot::create([
                'id' => (string) Str::uuid(),
                'order_id' => $orderId,
                'name_en' => $data->serviceSnapshot->nameEn,
                'name_ar' => $data->serviceSnapshot->nameAr,
                'descriptions' => $data->serviceSnapshot->descriptions,
                'requirements' => $data->serviceSnapshot->requirements,
                'expected_duration' => $data->serviceSnapshot->expectedDuration,
                'notes' => $data->serviceSnapshot->notes,
                'created_at' => $now,
            ]);

            OrderTravelerSnapshot::create([
                'id' => (string) Str::uuid(),
                'order_id' => $orderId,
                'full_name' => $data->travelerSnapshot->fullName,
                'date_of_birth' => $data->travelerSnapshot->dateOfBirth,
                'gender' => $data->travelerSnapshot->gender,
                'passport_number' => $data->travelerSnapshot->passportNumber,
                'passport_issued_at' => $data->travelerSnapshot->passportIssuedAt,
                'passport_expires_at' => $data->travelerSnapshot->passportExpiresAt,
                'created_at' => $now,
            ]);

            OrderFormSnapshot::create([
                'id' => (string) Str::uuid(),
                'order_id' => $orderId,
                'form_version_id' => $data->formSnapshot->formVersionId,
                'form_version' => $data->formSnapshot->formVersion,
                'form_checksum' => $data->formSnapshot->formChecksum,
                'schema' => $data->formSnapshot->schema,
                'answers' => $data->formSnapshot->answers,
                'created_at' => $now,
            ]);

            return $order->toPaidOrderData();
        });
    }
}
