<?php

declare(strict_types=1);

namespace Rehla\Web\Livewire\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Rehla\Fulfillment\Data\ExecutionDetails;
use Rehla\Fulfillment\Queries\GetOwnedExecution;
use Rehla\Orders\Data\PaidOrderData;
use Rehla\Orders\Exceptions\OrderNotFoundException;
use Rehla\Orders\Queries\GetOwnedOrder;

final class OrderShow extends Component
{
    public string $orderId = '';

    public function mount(string $orderId): void
    {
        $this->orderId = $orderId;
        $accountId = (string) Auth::id();

        try {
            app(GetOwnedOrder::class)->handle($accountId, $this->orderId);
        } catch (OrderNotFoundException $e) {
            abort(404);
        }
    }

    public function render(): View
    {
        $accountId = (string) Auth::id();

        /** @var PaidOrderData $order */
        $order = app(GetOwnedOrder::class)->handle($accountId, $this->orderId);

        /** @var ExecutionDetails|null $execution */
        $execution = app(GetOwnedExecution::class)->handleByOrderId($accountId, $this->orderId);

        return view('rehla-web::livewire.account.order-show', [
            'order' => $order,
            'execution' => $execution,
        ])->layout('rehla-web::layouts.app', [
            'title' => __('Order #:id', ['id' => substr($this->orderId, 0, 8)]),
        ]);
    }
}
