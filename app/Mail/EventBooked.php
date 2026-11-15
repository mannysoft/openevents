<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Log;
use App\Models\LoungeClub;
use App\Services\Order as OrderService;

class EventBooked extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public $pdfPath;

    public ?string $emailTemplate;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($order, $pdfPath = null)
    {
        $this->order = $order;

        $this->pdfPath = $pdfPath;

        $this->emailTemplate = $this->resolveEmailTemplate();
    }

    protected function resolveEmailTemplate(): ?string
    {
        $clubSlug = $this->order?->event?->club ?? null;

        if (! $clubSlug) {
            return null;
        }

        $club = LoungeClub::where('slug', $clubSlug)->first();

        if (! $club || empty($club->email_booking_template)) {
            return null;
        }

        // Variables to be replaced in the template
        $variables = [
            '{first_name}'     => $this->order->first_name,
            '{event_title}'    => $this->order->event->title,
            '{event_date}'     => $this->order->event->start_date->format('d M Y H:i A'),
            '{event_location}' => $this->order->event->location,
        ];

        return str_replace(array_keys($variables), array_values($variables), $club->email_booking_template);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order = $this->order;
        $orderService = new OrderService($order->amount, $order->organiser_booking_fee, $order->event);
        $orderService->calculateFinalCosts();
        $data = [
            'order'         => $order,
            'orderService'  => $orderService,
            'emailTemplate' => $this->emailTemplate,
        ];

        // The confirmation must go even if the PDF cannot be made: the email does not mention
        // an attachment, and its "View Ticket" link works without one.
        try {
            $pdfPath = generate_pdf_receipt($order);
        } catch (\Throwable $e) {
            $pdfPath = null;
            Log::error('Booking PDF could not be generated: sending the confirmation without it', [
                'order_reference' => $order->order_reference,
                'error' => class_basename($e),
                'message' => $e->getMessage(),
            ]);
        }

        $mail = $this->view('event-booked', $data)
            ->subject($this->order->event->title)
            ->to($this->order->email, $this->order->full_name);

        if ($pdfPath !== null && is_file($pdfPath)) {
            $mail->attach($pdfPath, [
                'as' => 'receipt-100000' . $this->order->id . '.pdf',
                'mime' => 'application/pdf',
            ]);
        } elseif ($pdfPath !== null) {
            Log::error('Booking PDF missing: sending the confirmation without it', [
                'order_reference' => $order->order_reference,
            ]);
        }

        return $mail;
    }
}
