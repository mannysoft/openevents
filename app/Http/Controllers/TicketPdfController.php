<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketPdfController extends Controller
{
    public function show(Attendee $attendee)
    {
        $attendee->load(['event.organiser', 'ticket', 'order']);

        return Pdf::loadView('pdf.ticket', [
            'attendee' => $attendee,
            'event' => $attendee->event,
        ])->download("ticket-{$attendee->reference}.pdf");
    }
}
