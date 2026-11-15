<?php

namespace App\Http\Controllers;

use App\Exports\AttendeesExport;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendeeExportController extends Controller
{
    public function __invoke(Request $request, Event $event)
    {
        Gate::authorize('view', $event);

        $format = $request->route('format') === 'xlsx' ? 'xlsx' : 'csv';

        $attendees = $event->attendees()
            ->with(['order', 'ticket'])
            ->whereHas('order', fn ($query) => $query->where('status', 'completed'))
            ->orderBy('id')
            ->get();

        $rows = $attendees->map(fn ($attendee) => [
            $attendee->reference,
            $attendee->name(),
            $attendee->email,
            $attendee->ticket?->title,
            $attendee->order?->reference,
            $attendee->is_cancelled ? 'Cancelled' : 'Active',
            $attendee->is_refunded ? 'Refunded' : '',
            $attendee->checked_in_at?->toDateTimeString() ?? '',
        ]);

        $headings = ['Reference', 'Name', 'Email', 'Ticket', 'Order', 'Status', 'Refunded', 'Checked in'];

        if ($format === 'xlsx') {
            return Excel::download(
                new AttendeesExport($headings, $rows->all()),
                "attendees-{$event->slug}.xlsx",
            );
        }

        return new StreamedResponse(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"attendees-{$event->slug}.csv\"",
        ]);
    }
}
