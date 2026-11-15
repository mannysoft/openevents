<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public Event $event;

    public string $status = 'all';

    public string $search = '';

    public string $direction = 'desc';

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);

        $this->event = $event;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'search', 'direction'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->status = 'all';
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $currency = $this->event->currency_code;
        $term = '%'.trim($this->search).'%';

        $orders = $this->event->orders()
            ->withCount('items')
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($query) use ($term) {
                $query->where(function ($query) use ($term) {
                    $query->where('reference', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term);
                });
            })
            ->orderBy('created_at', $this->direction === 'asc' ? 'asc' : 'desc')
            ->orderBy('id', $this->direction === 'asc' ? 'asc' : 'desc')
            ->paginate(15);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
            'currency' => $currency,
            'statuses' => OrderStatus::cases(),
            'grossCompletedMinor' => (int) $this->event->orders()->where('status', OrderStatus::Completed)->sum('total'),
            'refundedMinor' => (int) $this->event->orders()->sum('refunded_amount'),
            'orderCount' => $this->event->orders()->count(),
        ])->title($this->event->title.' — Orders');
    }
}
