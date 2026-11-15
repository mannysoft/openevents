<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use PDF;
use App\Models\Order;

class GenerateTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket:generate';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate sample ticket';

    protected $reference;
    protected $order_reference;
    protected $attendee_reference_index;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $reference = '6iXKY235';

        // config('attendize.event_pdf_tickets_path') . '/' . $reference . '.pdf';

        file_put_contents(public_path(config('attendize.event_pdf_tickets_path')) . '/' . $reference . '.pdf', fopen('https://api.globalwomanevents.com/tickets/' . $reference, 'r'));

        // file_put_contents('Lo4dc282.pdf', fopen('https://api.globalwomanevents.com/tickets/' . $reference, 'r'));
    }
}
