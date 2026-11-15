<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use App\Support\Money;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $organisers = Organiser::query()
            ->where('user_id', auth()->id())
            ->withCount('events')
            ->orderBy('name')
            ->get();

        $organiserIds = $organisers->pluck('id');

        $totals = Order::query()
            ->whereIn('organiser_id', $organiserIds)
            ->where('status', OrderStatus::Completed->value)
            ->selectRaw('currency_code, COUNT(*) as orders_count, SUM(total) as gross, SUM(application_fee_amount) as fees')
            ->groupBy('currency_code')
            ->get();

        $grossByCurrency = $totals->mapWithKeys(
            fn ($row) => [$row->currency_code => (int) $row->gross]
        );

        $feesByCurrency = $totals->mapWithKeys(
            fn ($row) => [$row->currency_code => (int) $row->fees]
        );

        $netByCurrency = $grossByCurrency->map(
            fn (int $gross, string $currency) => $gross - (int) $feesByCurrency->get($currency, 0)
        );

        $ticketsSold = $organiserIds->isEmpty() ? 0 : Attendee::query()
            ->where('is_cancelled', false)
            ->whereHas('event', fn ($query) => $query->whereIn('organiser_id', $organiserIds))
            ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Completed->value))
            ->count();

        $upcomingQuery = Event::query()
            ->whereIn('organiser_id', $organiserIds)
            ->where('start_date', '>=', now());

        $upcomingEventsCount = (clone $upcomingQuery)->count();

        $upcomingEvents = $upcomingQuery
            ->with('organiser')
            ->withCount(['attendees as tickets_sold_count' => fn ($query) => $query->where('is_cancelled', false)])
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        $recentOrders = Order::query()
            ->with('event')
            ->whereIn('organiser_id', $organiserIds)
            ->where('status', OrderStatus::Completed->value)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard', [
            'organisers' => $organisers,
            'needsOnboarding' => $organisers->contains(fn (Organiser $organiser) => ! $organiser->stripeReady()),
            'grossRevenue' => $this->moneyLines($grossByCurrency),
            'platformFees' => $this->moneyLines($feesByCurrency),
            'netToOrganisers' => $this->moneyLines($netByCurrency),
            'multipleCurrencies' => $grossByCurrency->count() > 1,
            'ticketsSold' => $ticketsSold,
            'ordersCount' => (int) $totals->sum('orders_count'),
            'upcomingEventsCount' => $upcomingEventsCount,
            'upcomingEvents' => $upcomingEvents,
            'recentOrders' => $recentOrders,
        ]);
    }

    private function moneyLines(Collection $amounts): array
    {
        if ($amounts->isEmpty()) {
            return [Money::format(0, config('events.default_currency'))];
        }

        return $amounts
            ->sortKeys()
            ->map(fn (int $minor, string $currency) => Money::format($minor, $currency))
            ->values()
            ->all();
    }
}
