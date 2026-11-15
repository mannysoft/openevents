<?php

namespace App\Livewire\Admin\Organisers;

use App\Models\Organiser;
use App\Services\Stripe\ConnectService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

#[Layout('components.layouts.app')]
class StripeConnect extends Component
{
    public Organiser $organiser;

    /** @var array{currently_due: array<int, array{description: string, awaiting_action_from: ?string, errors: array<int, string>}>, eventually_due: array<int, array{description: string, awaiting_action_from: ?string, errors: array<int, string>}>, disabled_reason: ?string} */
    public array $requirements = [];

    public function mount(Organiser $organiser): void
    {
        Gate::authorize('manageStripe', $organiser);

        $this->organiser = $organiser;
        $this->loadRequirements();
    }

    public function connect()
    {
        try {
            return redirect()->away(app(ConnectService::class)->onboardingUrl($this->organiser));
        } catch (ApiErrorException $e) {
            report($e);
            session()->flash('error', 'We could not reach Stripe to start onboarding. Please try again in a moment.');

            return $this->redirect(route('admin.organisers.stripe', $this->organiser), navigate: true);
        }
    }

    public function refresh()
    {
        $service = app(ConnectService::class);

        try {
            $service->refreshStatus($this->organiser);
        } catch (ApiErrorException $e) {
            report($e);
            session()->flash('error', 'We could not refresh your Stripe status. Please try again in a moment.');

            return $this->redirect(route('admin.organisers.stripe', $this->organiser), navigate: true);
        }

        $this->organiser->refresh();
        $this->loadRequirements();

        session()->flash('status', $this->organiser->stripeReady()
            ? 'Stripe is connected. You can now take payments for this organiser.'
            : 'Stripe status refreshed. Onboarding is not complete yet.');

        return $this->redirect(route('admin.organisers.stripe', $this->organiser), navigate: true);
    }

    public function dashboard()
    {
        try {
            return redirect()->away(app(ConnectService::class)->dashboardUrl($this->organiser));
        } catch (ApiErrorException $e) {
            report($e);
            session()->flash('error', 'We could not open the Stripe dashboard. Please try again in a moment.');

            return $this->redirect(route('admin.organisers.stripe', $this->organiser), navigate: true);
        }
    }

    private function loadRequirements(): void
    {
        try {
            $this->requirements = app(ConnectService::class)->requirements($this->organiser);
        } catch (ApiErrorException $e) {
            report($e);
            $this->requirements = ['currently_due' => [], 'eventually_due' => [], 'disabled_reason' => null];
            session()->flash('error', 'We could not load your Stripe account details. Use "Refresh status" to try again.');
        }
    }

    public function render()
    {
        return view('livewire.admin.organisers.stripe-connect')
            ->title($this->organiser->name.' · Stripe');
    }
}
