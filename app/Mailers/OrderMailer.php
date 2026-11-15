<?php

namespace App\Mailers;

use App\Mail\EventAgenda;
use App\Models\Order;
use App\Services\Order as OrderService;
use Log;
use Mail;

class OrderMailer
{
    public function sendOrderNotification(Order $order)
    {
        $orderService = new OrderService($order->amount, $order->organiser_booking_fee, $order->event);
        $orderService->calculateFinalCosts();
        $data = [
            'order' => $order,
            'orderService' => $orderService
        ];

        // This will send email to Mirela or account holder
        Mail::send('en.Emails.OrderNotification', $data, function ($message) use ($order) {
            $message->to($order->account->email);
            $message->subject(trans(
                "Controllers.new_order_received",
                ["event" => $order->event->title, "order" => $order->order_reference]
            ));
        });
    }

    public function sendOrderTickets(Order $order)
    {
        Mail::queue(new \App\Mail\EventBooked($order));

        // We can also send the agenda if the event is 3 days
        if (is_event_coming($order->event)) {

            // skip if orange county or zurich and london
            if (skip_agenda($order->event->club)) {
                return true;
            }
            Mail::queue(new EventAgenda($order));
        }
        return true;

        // Below is old email.
        $orderService = new OrderService($order->amount, $order->organiser_booking_fee, $order->event);
        $orderService->calculateFinalCosts();
        Log::info("Sending ticket to: " . $order->email);
        $data = [
            'order' => $order,
            'orderService' => $orderService
        ];
        $file_name = $order->order_reference;
        $file_path = public_path(config('attendize.event_pdf_tickets_path')) . '/' . $file_name . '.pdf';
        if (!file_exists($file_path)) {
            Log::error("Cannot send actual ticket to : " . $order->email . " as ticket file does not exist on disk");
            return;
        }
        try {
            Mail::send('en.Mailers.TicketMailer.SendOrderTickets', $data, function ($message) use ($order, $file_path) {
                $message->to($order->email);
                $message->subject(trans("Controllers.tickets_for_event", ["event" => $order->event->title]));
                $message->attach($file_path);
            });
        } catch (\Exception $e) {
            Log::error("Error Occurred sending tickets : " . $e->getMessage());
        }
    }
}
