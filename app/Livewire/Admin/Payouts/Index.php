<?php

namespace App\Livewire\Admin\Payouts;

use App\Enums\OrderStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payouts')]
class Index extends Component
{
    public ?string $organiserId = null;

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->toDateString();
    }

    public function render()
    {
        $organisers = Organiser::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $selectedOrganiser = $this->organiserId === null
            ? null
            : Organiser::query()->find($this->organiserId);

        if ($selectedOrganiser) {
            Gate::authorize('view', $selectedOrganiser);
        }

        [$from, $to] = $this->dateRange();

        $orderTotals = Order::query()
            ->whereIn('organiser_id', $organisers->pluck('id'))
            ->when($selectedOrganiser, fn ($query) => $query->where('organiser_id', $selectedOrganiser->id))
            ->where('status', OrderStatus::Completed->value)
            ->when($from, fn ($query) => $query->where('paid_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('paid_at', '<=', $to))
            ->selectRaw('event_id, currency_code, COUNT(*) as orders_count, SUM(total) as gross, SUM(application_fee_amount) as fees, SUM(refunded_amount) as refunded')
            ->groupBy('event_id', 'currency_code')
            ->get();

        $events = Event::query()
            ->with('organiser')
            ->whereIn('id', $orderTotals->pluck('event_id'))
            ->get()
            ->keyBy('id');

        $ticketsSold = Attendee::query()
            ->whereIn('event_id', $orderTotals->pluck('event_id'))
            ->where('is_cancelled', false)
            ->whereHas('order', function ($query) use ($from, $to) {
                $query->where('status', OrderStatus::Completed->value)
                    ->when($from, fn ($query) => $query->where('paid_at', '>=', $from))
                    ->when($to, fn ($query) => $query->where('paid_at', '<=', $to));
            })
            ->selectRaw('event_id, COUNT(*) as aggregate')
            ->groupBy('event_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->event_id => (int) $row->aggregate]);

        $rows = $orderTotals
            ->map(fn ($row) => [
                'event' => $events->get($row->event_id),
                'title' => $events->get($row->event_id)?->title ?? 'Deleted event',
                'currency' => $row->currency_code,
                'orders' => (int) $row->orders_count,
                'tickets' => (int) $ticketsSold->get($row->event_id, 0),
                'gross' => (int) $row->gross,
                'fees' => (int) $row->fees,
                'net' => (int) $row->gross - (int) $row->fees,
                'refunded' => (int) $row->refunded,
            ])
            ->sortBy('title')
            ->values();

        $totals = $rows
            ->groupBy('currency')
            ->map(fn ($group, string $currency) => [
                'currency' => $currency,
                'orders' => (int) $group->sum('orders'),
                'tickets' => (int) $group->sum('tickets'),
                'gross' => (int) $group->sum('gross'),
                'fees' => (int) $group->sum('fees'),
                'net' => (int) $group->sum('net'),
                'refunded' => (int) $group->sum('refunded'),
            ])
            ->sortKeys()
            ->values();

        return view('livewire.admin.payouts.index', [
            'organisers' => $organisers,
            'selectedOrganiser' => $selectedOrganiser,
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    /**
     * @return array{0: CarbonInterface|null, 1: CarbonInterface|null}
     */
    private function dateRange(): array
    {
        return [
            rescue(fn () => Carbon::parse($this->from)->startOfDay(), null, false),
            rescue(fn () => Carbon::parse($this->to)->endOfDay(), null, false),
        ];
    }
}
