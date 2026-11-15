<?php

namespace App\Http\Controllers;

class SessionController extends Controller
{
    public function setUserSession()
    {
        // Read the body only: buyer details must never travel in a URL.
        // The page posts on every debounced keystroke, so a half-typed email is normal here;
        // createOrUpdateCustomer() checks the format before it reaches Stripe.
        $details = validator(request()->post(), [
            'firstname' => ['nullable', 'string', 'max:100'],
            'lastname' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'max:254'],
            'phone' => ['nullable', 'string', 'max:32'],
            'club' => ['nullable', 'string', 'max:100'],
        ])->validate();

        session(['checkout_firstname' => $details['firstname'] ?? null]);
        session(['checkout_lastname' => $details['lastname'] ?? null]);
        session(['checkout_email' => $details['email'] ?? null]);
        session(['checkout_phone' => $details['phone'] ?? null]);

        $this->setStripeCustomer();

        return response()->json(['message' => 'Session variables set']);
    }

    // We can update the stripe customer here
    public function setStripeCustomer()
    {
        if (session('checkout_email') == null && session('email') == null) {
            return;
        }

        $stripeGateway = new \App\Services\StripeGateway();
        $stripeGateway->clubSlug = request('club', null);

        // The payment page made the PaymentIntent before she typed her email. Put it there now:
        // if she pays and never comes back, it is the only record of who paid.
        $stripeGateway->tagPaymentIntentEmail($stripeGateway->clubSlug, session('checkout_email') ?? session('email'));

        // If we have the necessary info, create or update the customer
        if (session('checkout_firstname') && session('checkout_lastname') && session('checkout_email')) {
            $customer = $stripeGateway->createOrUpdateCustomer(session('checkout_email') ?? session('email'));
            if ($customer) {
                session(['stripe_customer_id' => $customer]);
                // $stripeGateway->customer = $customer;
                // $stripeGateway->updateIntentDataCustomer();
            }
        }
    }
}
