<?php

namespace App\Http\Controllers;

use App\Models\Organiser;
use App\Services\Stripe\ConnectService;
use Illuminate\Http\Request;

class StripeConnectController extends Controller
{
    public function __construct(private readonly ConnectService $connect) {}

    public function return(Request $request, Organiser $organiser)
    {
        abort_unless($request->user()?->can('manageStripe', $organiser), 403);

        $this->connect->refreshStatus($organiser);

        return redirect()
            ->route('admin.organisers.stripe', $organiser)
            ->with('status', $organiser->stripeReady()
                ? 'Stripe account connected.'
                : 'Stripe onboarding is incomplete.');
    }

    public function refresh(Request $request, Organiser $organiser)
    {
        abort_unless($request->user()?->can('manageStripe', $organiser), 403);

        return redirect()
            ->route('admin.organisers.stripe', $organiser)
            ->with('status', 'Your onboarding link expired, please start again.');
    }
}
