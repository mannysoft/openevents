<?php

namespace App\Services\PaymentGateway;

class Stripe
{
    public const GATEWAY_NAME = 'Stripe';

    private $transaction_data;

    private $gateway;

    public $options = [];

    private $extra_params = ['stripeToken'];

    public function __construct($gateway)
    {
        $this->gateway = $gateway;
    }

    // https://docs.stripe.com/api/charges/create
    private function createTransactionData($order_total, $order_email, $event)
    {
        $this->transaction_data = [
            'amount' => $order_total,
            'currency' => $event->currency->code,
            'description' => $event->title,
            'token' => $this->options['stripeToken'],
            'receipt_email' => $order_email
        ];

        if (session('firstname') ?? session('checkout_firstname')) {
            $first_name = session('firstname') ?? session('checkout_firstname');
            $this->transaction_data['metadata']['first_name'] = $first_name;
        }
        if (session('lastname') ?? session('checkout_lastname')) {
            $last_name = session('lastname') ?? session('checkout_lastname');
            $this->transaction_data['metadata']['last_name'] = $last_name;
        }
        if (session('email') ?? session('checkout_email')) {
            $email = session('email') ?? session('checkout_email');
            $this->transaction_data['metadata']['email'] = $email;
        }
        if (session('phone') ?? session('checkout_phone')) {
            $phone = session('phone') ?? session('checkout_phone');
            $this->transaction_data['metadata']['phone'] = $phone;
        }

        $this->transaction_data['metadata']['event_id'] = $event->id;

        if (session('stripeCustomerId')) {
            $this->transaction_data['metadata']['stripe_customer_id'] = session('stripeCustomerId');
            // $this->transaction_data['customer'] = session('stripeCustomerId');
        }

        return $this->transaction_data;
    }

    public function startTransaction($order_total, $order_email, $event)
    {
        if ($order_email != "") {
            $this->createTransactionData($order_total, $order_email, $event);

            // For create customer. For future use.
            // $response = $this->gateway->createCustomer(array(
            //     'description'       => 'Test Customer',
            //     'email'             => 'test123@example.com',
            // ))->send();

            $transaction = $this->gateway->purchase($this->transaction_data);
            $response = $transaction->send();

            return $response;
        } else {
            $response = [];
            return $response;
        }
    }

    public function getTransactionData()
    {
        return $this->transaction_data;
    }

    public function extractRequestParameters($request)
    {
        foreach ($this->extra_params as $param) {
            if (!empty($request->get($param))) {
                $this->options[$param] = $request->get($param);
            }
        }
    }

    public function completeTransaction($data)
    {
    }

    public function getAdditionalData()
    {
    }

    public function storeAdditionalData()
    {
        return false;
    }

    public function refundTransaction($order, $refund_amount, $refund_application_fee)
    {

        $request = $this->gateway->refund([
            'transactionReference' => $order->transaction_id,
            'amount' => $refund_amount,
            'refundApplicationFee' => $refund_application_fee
        ]);

        $response = $request->send();

        if ($response->isSuccessful()) {
            $refundResponse['successful'] = true;
        } else {
            $refundResponse['successful'] = false;
            $refundResponse['error_message'] = $response->getMessage();
        }

        return $refundResponse;
    }
}
