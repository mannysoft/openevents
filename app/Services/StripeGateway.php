<?php

namespace App\Services;

use Stripe\StripeClient;
use App\Models\LoungeClub;
use Illuminate\Support\Facades\Log;

// Need to work on VAT and Tax later like liverpool club

class StripeGateway
{
    public $stripeAccountId = null;

    public $currencyCode = 'GBP';

    public $eventId = null;

    public $eventTitle = null;

    public $eventDate = null;

    public $latestCharge = null;

    public $clubSlug = null;

    public $taxAmount = 0;

    public $customer = null;

    public $charge = null;

    public function getApplicationFee($chargeAmount)
    {
        if ($this->stripeAccountId == null) {
            return 0;
        }

        $club = LoungeClub::where('slug', $this->clubSlug)->first();

        $stripeFeePercent = 0.029;
        $stripeFeeFixed = 30;

        if ($club) {
            // If club has stripe fee settings then use them
            $stripeFeePercent = ($club->stripe_fee_percent / 100) / 100;
            $stripeFeeFixed = intval($club->stripe_fee_fixed);
        } else {
            Log::warning('Club not found for slug: '.$this->clubSlug);
            return 0;
        }

        $stripeFeeAmount = $this->estimateStripeFee($chargeAmount, $stripeFeePercent, $stripeFeeFixed);

        $netAmount = (int)$chargeAmount - $stripeFeeAmount;

        // We are taking 50% of the net amount as application fee
        return intval(floor($netAmount / 2)); // in cents
    }

    public function getClientSecret($amount, $accountId = null)
    {
        $this->stripeAccountId = $accountId;
        // session()->forget('stripeClientSecret');
        $stripe = new StripeClient(config('services.stripe.secret'));

        // For payment intent
        // https://docs.stripe.com/api/payment_intents/create?api-version=2025-08-27.basil

        // $fee = (5000 * (29 / 100)) + 30;

        $chargeAmount = intval($amount); // $70

        $applicationFeeAmount = $this->getApplicationFee($chargeAmount);

        // Test account
        // latest@globalwomanclub.com / buxfAk-xyrno8-juctox
        // This works on sandbox account acct_1SDiI6PEcsDLKtcI

        // NEED TO USE DIRECT CHARGE IF USING CONNECTED ACCOUNT
        // https://docs.stripe.com/connect/charges#direct
        // $accountId = 'acct_1S8IISAEbYPD1pn4';

        // If in case that we are unable to change the connected account
        // and the application is charge with stripe fee
        // We need to increase the amount to cover the fee
        // $applicationFee = intval(floor($netAmount / 2)) + $stripeFeeAmount;

        $intentOptions = null;

        $intentData = [
            'amount' => $chargeAmount,
            'currency' => $this->currencyCode,
            'payment_method_types' => ['card'], // restrict to card only
            // 'receipt_email' => session('checkoutEmail') ?? null,
            // 'automatic_payment_methods' => ['enabled' => true],
            'payment_method_options' => ['card' => ['request_three_d_secure' => 'any']],
        ];

        if ($this->stripeAccountId != null && $applicationFeeAmount > 0) {
            $intentData['application_fee_amount'] = $applicationFeeAmount; // in cents
            // Use connected account
            $intentOptions = ['stripe_account' => $accountId];
        }

        if ($this->eventTitle != null) {
            $intentData['description'] = $this->eventTitle;

            $slotName = session()->has('speaking_slot_details') ? session('speaking_slot_details')->name : null;

            if ($slotName != null) {
                $intentData['description'] .= ' x ' . $slotName;
            }
        }

        if ($this->taxAmount != 0) {
            $intentData['metadata']['tax_amount'] = (string)($this->taxAmount / 100);
            $intentData['metadata']['currency'] = $this->currencyCode;
        }

        $intentData['metadata']['event_id'] = $this->eventId ?? 'N/A';
        $intentData['metadata']['event_title'] = $this->eventTitle ?? 'N/A';
        $intentData['metadata']['event_date'] = $this->eventDate ?? 'N/A';

        // The order is only written when the buyer comes back from Stripe. If she never does,
        // the email on the PaymentIntent is the only record of who paid (the already-purchased
        // check and events:check-missed-payments read it). The charge only gets it on return.
        $buyerEmail = EventPaymentLookup::normaliseEmail(session('checkout_email') ?? session('email'));

        if ($buyerEmail !== null) {
            $intentData['metadata']['email'] = $buyerEmail;
        }

        if (session('referal_email_accepted') === true && session('referal_email')) {
            $intentData['metadata']['referal_email'] = session('referal_email');
            $intentData['metadata']['referal_first_name'] = session('referal_first_name') ?? 'N/A';
            $intentData['metadata']['referal_last_name'] = session('referal_last_name') ?? 'N/A';
        }

        if ($this->customer != null) {

            // Check if customer exists first. If not, we may need to create it first.
            // Get the account customer id
            try {
                $customer = $stripe->customers->retrieve($this->customer, $intentOptions);
                // $intentData['customer'] = $this->customer;
                $intentData['customer'] = $customer->id;

            } catch (\Exception $e) {
                // Customer does not exist
            }
        }

        $clubSlug = self::intentSessionSlug($this->clubSlug);

        // Which account holds this club's PaymentIntent, so the buyer's email can be added to
        // it later (tagPaymentIntentEmail) without guessing.
        $intentAccount = $intentOptions['stripe_account'] ?? EventPaymentLookup::PLATFORM;

        if (session('stripeClientSecret.'.$clubSlug) && session('stripePaymentIntentId.'.$clubSlug)) {
            // Update the amount if needed
            unset($intentData['automatic_payment_methods']);
            try {
                $intent = $stripe->paymentIntents->update(
                    session('stripePaymentIntentId.'.$clubSlug),
                    $intentData,
                    $intentOptions
                );

            } catch (\Exception $e) {
                return false;
            }

            session(['stripePaymentIntentAccount.'.$clubSlug => $intentAccount]);

            if ($buyerEmail !== null) {
                session(['stripePaymentIntentTagged.'.$clubSlug => session('stripePaymentIntentId.'.$clubSlug).'|'.$buyerEmail]);
            }

            return ['clientSecret' => session('stripeClientSecret.'.$clubSlug)];
        }

        // The old way of doing it is w/o the account id
        // $intent = $stripe->paymentIntents->create($intentData);
        // The new way is to create the payment intent on the connected account
        // This way, the connected account is responsible for the payment
        // and we don't have to worry about compliance issues
        // https://stripe.com/docs/connect/charges#creating-direct-charges
        $intent = $stripe->paymentIntents->create($intentData, $intentOptions);

        session([
            'stripeClientSecret.'.$clubSlug => $intent->client_secret,
            'stripePaymentIntentId.'.$clubSlug => $intent->id,
            'stripePaymentIntentAccount.'.$clubSlug => $intentAccount,
        ]);

        if ($buyerEmail !== null) {
            session(['stripePaymentIntentTagged.'.$clubSlug => $intent->id.'|'.$buyerEmail]);
        }

        return ['clientSecret' => $intent->client_secret];
    }

    /**
     * The session slot getClientSecret() keeps a club's PaymentIntent under: the club slug, or
     * 'club' when there is none.
     */
    public static function intentSessionSlug(?string $clubSlug): string
    {
        return $clubSlug ?? 'club';
    }

    /**
     * The PaymentIntent of the checkout in progress for this club, if any.
     */
    public static function currentIntentId(?string $clubSlug): ?string
    {
        $intentId = session('stripePaymentIntentId.'.self::intentSessionSlug($clubSlug));

        return is_string($intentId) && $intentId !== '' ? $intentId : null;
    }

    /**
     * Puts the buyer's email on this checkout's PaymentIntent.
     *
     * The payment page creates the PaymentIntent before a signed-out buyer has typed her email,
     * so it arrives later through set-user-session. Writes only when the email or the
     * PaymentIntent has changed. A Stripe error is logged and never blocks the checkout.
     */
    public function tagPaymentIntentEmail(?string $clubSlug, ?string $email): bool
    {
        $email = EventPaymentLookup::normaliseEmail($email);
        $clubSlug = self::intentSessionSlug($clubSlug);

        $intentId = session('stripePaymentIntentId.'.$clubSlug);
        $account = session('stripePaymentIntentAccount.'.$clubSlug);

        // No PaymentIntent yet, or one created before its account was recorded: nothing to do.
        if ($email === null || ! is_string($intentId) || ! is_string($account) || $account === '') {
            return false;
        }

        $tag = $intentId.'|'.$email;

        if (session('stripePaymentIntentTagged.'.$clubSlug) === $tag) {
            return false;
        }

        try {
            (new StripeClient(config('services.stripe.secret')))->paymentIntents->update(
                $intentId,
                ['metadata' => ['email' => $email]],
                $account === EventPaymentLookup::PLATFORM ? null : ['stripe_account' => $account]
            );
        } catch (\Exception $e) {
            Log::warning('Could not add the buyer email to PaymentIntent '.$intentId.': '.class_basename($e));

            return false;
        }

        session(['stripePaymentIntentTagged.'.$clubSlug => $tag]);

        return true;
    }

    /**
     * Estimate the Stripe processing fee for a given amount.
     *
     * @param float $amount The payment amount in dollars.
     * @param float $percentage The Stripe fee percentage (default 2.9).
     * @param float $fixed_fee The fixed Stripe fee per transaction (default 0.30).
     * @return float The estimated Stripe fee in dollars.
     */
    public function estimateStripeFee(int $amount, $percentage = 0.029, int $fixed_fee = 30): int
    {
        return ($amount * $percentage) + $fixed_fee;
    }

    // Get the first charge from a payment intent
    public function getCharge($pi)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $intentOptions = null;

        if ($this->stripeAccountId != null) {
            $intentOptions = ['stripe_account' => $this->stripeAccountId];
        }

        $intent = $stripe->paymentIntents->retrieve(
            $pi,
            [],
            $intentOptions
        );

        $this->latestCharge = $intent->latest_charge;

        if (isset($intent->charges->data[0])) {
            $this->charge = $intent->charges->data[0];
            return $intent->charges->data[0];
        }

        return null;
    }

    public function updateChargeMeta()
    {
        if ($this->latestCharge == null) {
            return;
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $intentOptions = null;

        if ($this->stripeAccountId != null) {
            $intentOptions = ['stripe_account' => $this->stripeAccountId];
        }

        $chargeData = [
            'metadata' => [
                'first_name' => session('checkout_firstname'),
                'last_name' => session('checkout_lastname'),
                'email' => session('checkout_email') ?? session('email'),
                'phone' => session('checkout_phone'),
            ],
            'receipt_email' => session('checkout_email') ?? session('email'),
        ];

        if (session('referal_email_accepted') === true && session('referal_email')) {
            $chargeData['metadata']['referal_email'] = session('referal_email');
            $chargeData['metadata']['referal_first_name'] = session('referal_first_name') ?? 'N/A';
            $chargeData['metadata']['referal_last_name'] = session('referal_last_name') ?? 'N/A';
        }

        if (session('stripe_customer_id')) {
            $chargeData['customer'] = session('stripe_customer_id');
        }

        try {
            $stripe->charges->update(
                $this->latestCharge,
                $chargeData,
                $intentOptions
            );
        } catch (\Exception $e) {
            Log::error('Error checking latest charge: '.$e->getMessage());
        }
    }

    public function retrieveAccount($stripeAccountId)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        try {
            $account = $stripe->accounts->retrieve(
                $stripeAccountId,
                []
            );
        } catch (\Exception $e) {
            Log::error('Stripe Account Retrieval Error: '.$e->getMessage());
            return false;
        }

        return $account;
    }

    public function updateIntentDataCustomer($intentId = null)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $intentOptions = null;

        if ($this->stripeAccountId != null) {
            $intentOptions = ['stripe_account' => $this->stripeAccountId];
        }

        if (session('stripeClientSecret.'.$this->clubSlug) && session('stripePaymentIntentId.'.$this->clubSlug)) {
            $stripe->paymentIntents->update(
                session('stripePaymentIntentId.'.$this->clubSlug),
                ['customer' => $this->customer],
                $intentOptions
            );

            return ['clientSecret' => session('stripeClientSecret.'.$this->clubSlug)];
        }
    }

    public function createOrUpdateCustomer($email = null)
    {
        if ($email == null) {
            return null;
        }
        // If we already have the customer in session, use it
        if (session('stripe_customer_id') && $email == session('stripe_email')) {
            $this->customer = session('stripe_customer_id');
            return $this->customer;
        }

        // Make sure that the email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $options = null;

        $club = LoungeClub::where('slug', $this->clubSlug)->first();

        if ($club) {

            if ($club->is_onboarded) {
                $isAccountExists = $this->retrieveAccount($club->stripe_account_id);

                if ($isAccountExists == true) {
                    $this->stripeAccountId = $club->stripe_account_id;

                    $options = ['stripe_account' => $this->stripeAccountId];
                }
            }
        }

        $stripe = new StripeClient(config('services.stripe.secret'));
        $customer = $stripe->customers->search(
            [
                'query' => "email:'" . $email . "'",
            ],
            $options
        );

        session(['stripe_email' => $email]);

        // If customer exists, use it
        if (isset($customer->data[0])) {
            $this->customer = $customer->data[0]->id;

            // We can check if the customer details need to be updated later
            // We may need to check if the customer has name on it.
            if ($customer->data[0]->name == null || $customer->data[0]->name == '') {
                try {
                    $newCustomer = $stripe->customers->update(
                        $this->customer,
                        ['name' => session('checkout_firstname') . ' ' . session('checkout_lastname'),
                            'metadata' => [
                                'first_name' => session('checkout_firstname'),
                                'last_name' => session('checkout_lastname'),
                                'phone' => session('checkout_phone'),
                            ],
                        ],
                        $options
                    );

                } catch (\Exception $e) {
                    Log::error('Stripe Customer Updating Error: '.$e->getMessage());
                }
            }

            return $this->customer;
        } else {
            // Create new customer
            $customerDetails = [
                'email' => $email,
                'metadata' => [
                    'first_name' => session('checkout_firstname'),
                    'last_name' => session('checkout_lastname'),
                    'phone' => session('checkout_phone'),
                ],
            ];

            $customerDetails['name'] = session('checkout_firstname') . ' ' . session('checkout_lastname');

            try {
                $newCustomer = $stripe->customers->create(
                    $customerDetails,
                    $options
                );

                $this->customer = $newCustomer->id;
                return $this->customer;
            } catch (\Exception $e) {
                Log::error('Stripe Customer Creation Error: '.$e->getMessage());
            }
        }
    }

    public function getPaymentIntent($pi)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $intentOptions = null;

        if ($this->stripeAccountId != null) {
            $intentOptions = ['stripe_account' => $this->stripeAccountId];
        }

        $intent = $stripe->paymentIntents->retrieve(
            $pi,
            [],
            $intentOptions
        );

        return $intent;
    }

    public function transfer($chargeId, $clubStripeAccountId, $description = 'Commision', $order)
    {
        $stripe = new StripeClient(config('services.stripe.transfer_secret'));

        $charge = $stripe->charges->retrieve(
            $chargeId,
            ['expand' => ['balance_transaction']],
            ['stripe_account' => $this->stripeAccountId]
        );

        if ($charge->status != 'succeeded') {
            Log::error('Charge not succeeded for charge ID: '.$chargeId);
            return false;
        }

        // We need to get the stripe fee from the charge to make sure
        // that we are transferring the correct amount to director
        $stripeFeeAmount = 0;

        if (isset($charge->balance_transaction->fee)) {
            $stripeFeeAmount = $charge->balance_transaction->fee;
        } else {
            Log::warning('Stripe fee not found for charge ID: '.$chargeId);
        }
    
        $amount = intval($order->amount * 100);

        // dd('Original amount: '.$amount, 'Stripe fee amount: '.$stripeFeeAmount);

        $amount = $amount - $stripeFeeAmount;
        // dd('remaining amount: '.$amount);

        // Get 30% of the amount as commission
        $commission = intval(floor($amount * 0.3));

        // dd('commission amount: '.$commission);

        // $remain = intval(floor($amount * 0.7));
        // dd('remain amount: '.$remain);

        $metaData = [
            'event_id' => $order->event?->id ?? 'N/A',
            'event_title' => $order->event?->title ?? 'N/A',
            'event_date' => $order->event?->event_date ?? 'N/A',
            'first_name' => $order->first_name ?? 'N/A',
            'last_name' => $order->last_name ?? 'N/A',
            'contact_no' => $order->contact_no ?? 'N/A',
            'email' => $order->email ?? 'N/A',
            'club_commission' => $order->club_commission ?? 'N/A',
            'amount' => $order->amount ?? 'N/A',
        ];

        $transfer = $stripe->transfers->create([
            'amount' => $commission, // in cents
            'source_transaction' => $chargeId, // this did the trick for me and also resolve the Insufficient fund issue
            'currency' => 'GBP',
            'destination' => $clubStripeAccountId,
            'description' => $description,
            'metadata' => $metaData,
        ]);

        // Update the destination payment on the connected account so they see
        // a descriptive text instead of just the payment ID (py_xxx)
        if (isset($transfer->destination_payment)) {
            try {
                $stripe->charges->update(
                    $transfer->destination_payment,
                    ['description' => $description, 'metadata' => $metaData],
                    ['stripe_account' => $clubStripeAccountId]
                );
            } catch (\Exception $e) {
                Log::error('Error updating destination payment description: '.$e->getMessage());
            }
        }

        return $transfer;
    }

}
