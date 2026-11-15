<?php

namespace App\Mail;

use App\Models\Attendee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Attendee $attendee) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ticket for '.$this->attendee->event->title,
        );
    }

    public function content(): Content
    {
        $this->attendee->loadMissing(['event', 'ticket']);

        return new Content(
            markdown: 'emails.ticket-issued',
            with: [
                'event' => $this->attendee->event,
                'ticket' => $this->attendee->ticket,
            ],
        );
    }
}
