<?php

namespace App\Mailers;

use App\Models\Attendee;
use App\Models\Message;
use Carbon\Carbon;
use Log;
use Mail;


class AttendeeMailer extends Mailer
{

    public function sendAttendeeTicket($attendee)
    {

        Log::info("Sending ticket to: " . $attendee->email);

        $this->sendWithTicketPdf($attendee, 'en.Mailers.TicketMailer.SendAttendeeTicket');
    }

    /**
     * Sends the attendees a message
     *
     * @param Message $message_object
     */
    public function sendMessageToAttendees(Message $message_object)
    {
        $event = $message_object->event;

        $attendees = ($message_object->recipients == 'all')
            ? $event->attendees // all attendees
            : Attendee::where('ticket_id', '=', $message_object->recipients)->where('account_id', '=',
                $message_object->account_id)->get();

        foreach ($attendees as $attendee) {
            
            if ($attendee->is_cancelled) {
               continue;
            }
            
            $data = [
                'attendee'        => $attendee,
                'event'           => $event,
                'message_content' => $message_object->message,
                'subject'         => $message_object->subject,
                'email_logo'      => $attendee->event->organiser->full_logo_path,
            ];

            Mail::send('Emails.messageReceived', $data, function ($message) use ($attendee, $data) {
                $message->to($attendee->email, $attendee->full_name)
                    ->from(config('attendize.outgoing_email_noreply'), $attendee->event->organiser->name)
                    ->replyTo($attendee->event->organiser->email, $attendee->event->organiser->name)
                    ->subject($data['subject']);
            });
        }

        $message_object->is_sent = 1;
        $message_object->sent_at = Carbon::now();
        $message_object->save();
    }

    public function SendAttendeeInvite($attendee)
    {

        Log::info("Sending invite to: " . $attendee->email);

        $this->sendWithTicketPdf($attendee, 'en.Mailers.TicketMailer.SendAttendeeInvite');
    }

    /**
     * Sends a ticket email with the attendee's PDF ticket attached when it exists.
     *
     * The PDF is made by GenerateTicket just before this, and that can fail. Without the PDF the
     * email still goes, with copy that does not mention an attachment, and the miss is logged:
     * the attendee is already on the list, so the email must not fail (and roll back an invite)
     * over a file.
     *
     * The view names carry the en. prefix: the unprefixed Mailers.TicketMailer.* views do not
     * exist in this repository, only the per-language copies under resources/views/<lang>/.
     */
    private function sendWithTicketPdf($attendee, string $view): void
    {
        $reference = $attendee->getReferenceAttribute();
        $pdfPath = public_path(config('attendize.event_pdf_tickets_path')) . '/' . $reference . '.pdf';
        $ticketAttached = is_file($pdfPath) && is_readable($pdfPath);

        if (!$ticketAttached) {
            Log::error('Ticket PDF missing: sending the ticket email without it', [
                'attendee_reference' => $reference,
                'event_id' => $attendee->event_id,
            ]);
        }

        $orderReference = $attendee->order->order_reference;

        $data = [
            'attendee' => $attendee,
            'ticketAttached' => $ticketAttached,
            'orderReference' => $orderReference,
            'orderUrl' => route('showOrderDetails', ['order_reference' => $orderReference]),
        ];

        Mail::send($view, $data, function ($message) use ($attendee, $pdfPath, $ticketAttached) {
            $message->to($attendee->email);
            $message->subject(trans("Email.your_ticket_for_event", ["event" => $attendee->order->event->title]));

            if ($ticketAttached) {
                $message->attach($pdfPath);
            }
        });
    }


}
