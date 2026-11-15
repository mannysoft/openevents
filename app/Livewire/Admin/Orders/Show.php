<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Stripe\RefundService;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Order $order;

    public bool $showRefundModal = false;

    public $refundAmount = '';

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);

        $this->order = $order;
    }

    public function openRefundModal(): void
    {
        Gate::authorize('refund', $this->order);

        if (! $this->order->isRefundable()) {
            session()->flash('error', 'This order cannot be refunded.');

            return;
        }

        $this->resetErrorBag('refundAmount');
        $this->refundAmount = $this->refundableInput();
        $this->showRefundModal = true;
    }

    public function closeRefundModal(): void
    {
        $this->showRefundModal = false;
        $this->refundAmount = '';
        $this->resetErrorBag('refundAmount');
    }

    public function refund(RefundService $refundService, OrderService $orderService): void
    {
        Gate::authorize('refund', $this->order);

        $this->validate([
            'refundAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $amountMinor = Money::toMinor((string) $this->refundAmount, $this->order->currency_code);

        if ($amountMinor > $this->order->refundableMinor()) {
            $this->addError('refundAmount', 'The refund amount cannot exceed the refundable balance.');

            return;
        }

        try {
            $refundService->refund($this->order, $amountMinor);
            $orderService->markRefunded($this->order, $amountMinor);
        } catch (ValidationException $exception) {
            $this->addError('refundAmount', collect($exception->errors())->flatten()->first() ?? 'The refund could not be processed.');

            return;
        } catch (ApiErrorException $exception) {
            $this->addError('refundAmount', $exception->getMessage() ?: 'Stripe rejected the refund.');

            return;
        }

        $this->showRefundModal = false;
        $this->refundAmount = '';
        $this->order->refresh();

        session()->flash('status', 'Refund of '.Money::format($amountMinor, $this->order->currency_code).' issued.');
    }

    public function cancelOrder(OrderService $orderService): void
    {
        Gate::authorize('view', $this->order);

        if ($this->order->status !== OrderStatus::Reserved) {
            return;
        }

        $orderService->cancel($this->order);
        $this->order->refresh();

        session()->flash('status', 'Order cancelled.');
    }

    private function refundableInput(): string
    {
        return number_format(
            Money::toMajor($this->order->refundableMinor(), $this->order->currency_code),
            Money::decimals($this->order->currency_code),
            '.',
            '',
        );
    }

    public function render()
    {
        $currency = $this->order->currency_code;

        return view('livewire.admin.orders.show', [
            'currency' => $currency,
            'items' => $this->order->items()->with('ticket')->get(),
            'attendees' => $this->order->attendees()->with('ticket')->orderBy('id')->get(),
        ])->title('Order '.$this->order->reference);
    }
}
