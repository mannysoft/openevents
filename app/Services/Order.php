<?php

namespace App\Services;

use App\Models\Event;

class Order
{
    /**
     * @var float
     */
    private $orderTotal;

    /**
     * @var float
     */
    public $totalBookingFee;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var float
     */
    public $orderTotalWithBookingFee;

    /**
     * @var float
     */
    public $taxAmount;

    /**
     * @var float
     */
    public $grandTotal;

    /**
     * Order constructor.
     * @param $orderTotal
     * @param $totalBookingFee
     * @param $event
     */
    public function __construct($orderTotal, $totalBookingFee, $event)
    {

        $this->orderTotal = $orderTotal;
        $this->totalBookingFee = $totalBookingFee;
        $this->event = $event;
    }


    /**
     * Calculates the final costs for an event and sets the various totals
     */
    public function calculateFinalCosts()
    {
        $this->orderTotalWithBookingFee = $this->orderTotal + $this->totalBookingFee;

        if ($this->event->organiser->charge_tax == 1) {
            $this->taxAmount = ($this->orderTotalWithBookingFee * $this->event->organiser->tax_value) / 100;
        } else {
            $this->taxAmount = 0;
        }

        $this->grandTotal = $this->orderTotalWithBookingFee + $this->taxAmount;
    }

    /**
     * @param bool $currencyFormatted
     * @return float|string
     */
    public function getOrderTotalWithBookingFee($currencyFormatted = false)
    {

        if ($currencyFormatted == false) {
            return number_format($this->orderTotalWithBookingFee, 2, '.', '');
        }

        $subtotal = $this->orderTotalWithBookingFee + $this->speakingSlotsAmount();

        return money($subtotal, $this->event->currency);
    }

    /**
     * @param bool $currencyFormatted
     * @return float|string
     */
    public function getTaxAmount($currencyFormatted = false)
    {

        if ($currencyFormatted == false) {
            return number_format($this->taxAmount, 2, '.', '');
        }

        return money($this->taxAmount, $this->event->currency);
    }

    /**
     * @param bool $currencyFormatted
     * @return float|string
     */
    public function getGrandTotal($currencyFormatted = false)
    {

        if ($currencyFormatted == false) {
            return number_format($this->grandTotal + $this->speakingSlotsAmount(), 2, '.', '');
        }

        return money($this->grandTotal + $this->speakingSlotsAmount(), $this->event->currency);
    }


    public function speakingSlotsAmount()
    {
        // dd($this->orderTotal);
        // dd($this->event->orders);
        // dd($this->event->id);
        // foreach ($this->event->orders as $order) {
        //     dd($order->orderItems);
        // }
        // dd($this->event->orders());
        if (session()->has('speaking_slot_details')) {
            $slot = session('speaking_slot_details');
            return $slot->price;
        }

        return 0;
    }

    /**
     * @return string
     */
    public function getVatFormattedInBrackets()
    {
        return "(+" . $this->getTaxAmount(true) . " " . $this->event->organiser->tax_name . ")";
    }

}
