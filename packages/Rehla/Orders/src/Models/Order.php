<?php

declare(strict_types=1);

namespace Rehla\Orders\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Rehla\Orders\Data\OrderSummaryData;
use Rehla\Orders\Data\PaidOrderData;

final class Order extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'orders';

    protected $fillable = [
        'id',
        'account_id',
        'service_id',
        'traveler_id',
        'price_minor',
        'amount_paid_minor',
        'currency',
        'debit_ledger_entry_id',
        'financial_status',
        'created_at',
    ];

    protected $casts = [
        'price_minor' => 'integer',
        'amount_paid_minor' => 'integer',
        'created_at' => 'immutable_datetime',
    ];

    /**
     * @return HasOne<OrderServiceSnapshot, $this>
     */
    public function serviceSnapshot(): HasOne
    {
        return $this->hasOne(OrderServiceSnapshot::class, 'order_id');
    }

    /**
     * @return HasOne<OrderTravelerSnapshot, $this>
     */
    public function travelerSnapshot(): HasOne
    {
        return $this->hasOne(OrderTravelerSnapshot::class, 'order_id');
    }

    /**
     * @return HasOne<OrderFormSnapshot, $this>
     */
    public function formSnapshot(): HasOne
    {
        return $this->hasOne(OrderFormSnapshot::class, 'order_id');
    }

    public function toPaidOrderData(): PaidOrderData
    {
        $this->loadMissing(['serviceSnapshot', 'travelerSnapshot', 'formSnapshot']);

        return new PaidOrderData(
            id: (string) $this->id,
            accountId: (string) $this->account_id,
            serviceId: (string) $this->service_id,
            travelerId: (string) $this->traveler_id,
            priceMinor: (int) $this->price_minor,
            amountPaidMinor: (int) $this->amount_paid_minor,
            currency: (string) $this->currency,
            debitLedgerEntryId: (string) $this->debit_ledger_entry_id,
            financialStatus: (string) $this->financial_status,
            createdAt: DateTimeImmutable::createFromInterface($this->created_at),
            serviceSnapshot: $this->serviceSnapshot->toData(),
            travelerSnapshot: $this->travelerSnapshot->toData(),
            formSnapshot: $this->formSnapshot->toData(),
        );
    }

    public function toSummaryData(): OrderSummaryData
    {
        $this->loadMissing(['serviceSnapshot', 'travelerSnapshot']);

        return new OrderSummaryData(
            id: (string) $this->id,
            accountId: (string) $this->account_id,
            serviceId: (string) $this->service_id,
            travelerId: (string) $this->traveler_id,
            priceMinor: (int) $this->price_minor,
            currency: (string) $this->currency,
            financialStatus: (string) $this->financial_status,
            createdAt: DateTimeImmutable::createFromInterface($this->created_at),
            serviceNameEn: (string) $this->serviceSnapshot->name_en,
            serviceNameAr: (string) $this->serviceSnapshot->name_ar,
            travelerFullName: (string) $this->travelerSnapshot->full_name,
        );
    }
}
