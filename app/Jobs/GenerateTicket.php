<?php

namespace App\Jobs;

use App\Models\Order;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PDF;
use Spatie\Browsershot\Browsershot;
use Throwable;

class GenerateTicket extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $reference;
    protected $order_reference;
    protected $attendee_reference_index;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($reference)
    {
        Log::info("Generating ticket: #" . $reference);
        $this->reference = $reference;
        $this->order_reference = explode("-", $reference)[0];
        if (strpos($reference, "-")) {
            $this->attendee_reference_index = explode("-", $reference)[1];
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $reference = $this->reference;

        try {
            $this->browsershot(config('app.url') . '/order/' . $reference . '/tickets')
                ->save(public_path(config('attendize.event_pdf_tickets_path')) . '/' . $reference . '.pdf');

            Log::info('Ticket generated!');
        } catch (Throwable $e) {
            // The job still succeeds: the mailers send without the PDF when it is missing.
            // This line is how anyone finds out the PDF was not made, and why.
            Log::error('Ticket PDF could not be generated', [
                'reference' => $reference,
                'order_reference' => $this->order_reference,
                'attendee_reference_index' => $this->attendee_reference_index,
                'chrome_path' => config('app.chrome_path'),
                'error' => class_basename($e),
                'message' => $e->getMessage(),
            ]);
        }

        return; //  Make this first to remove any error.

        $file_name = $this->reference;
        $file_path = public_path(config('attendize.event_pdf_tickets_path')) . '/' . $file_name;
        $file_with_ext = $file_path . ".pdf";

        if (file_exists($file_with_ext)) {
            Log::info("Use ticket from cache: " . $file_with_ext);
            return;
        }

        $order = Order::where('order_reference', $this->order_reference)->first();
        Log::info($order);
        $event = $order->event;

        $query = $order->attendees();
        if ($this->isAttendeeTicket()) {
            $query = $query->where('reference_index', '=', $this->attendee_reference_index);
        }
        $attendees = $query->get();

        $image_path = $event->organiser->full_logo_path;
        $images = [];
        $imgs = $order->event->images;
		
        foreach ($imgs as $img) {
			$eventImage = 'https://davxegdvkjbks.cloudfront.net/'.$img->image_path;
            $images[] = base64_encode($eventImage);
        }

        $data = [
            'order'     => $order,
            'event'     => $event,
            'attendees' => $attendees,
            'css'       => file_get_contents(public_path('assets/stylesheet/ticket.css')),
            'image'     => base64_encode(file_get_contents(public_path($image_path))),
            'images'    => $images,
        ];
        try {
            PDF::setOutputMode('F'); // force to file
            PDF::html('Public.ViewEvent.Partials.PDFTicket', $data, $file_path);
            Log::info("Ticket generated!");
        } catch(\Exception $e) {
            Log::error("Error generating ticket. This can be due to permissions on vendor/nitmedia/wkhtml2pdf/src/Nitmedia/Wkhtml2pdf/lib. This folder requires write and execute permissions for the web user");
            Log::error("Error message. " . $e->getMessage());
            Log::error("Error stack trace" . $e->getTraceAsString());
            $this->fail($e);
        }

    }

    private function isAttendeeTicket()
    {
        return ($this->attendee_reference_index != null);
    }

    /**
     * Browsershot set up from config (config/app.php): Chrome, Node and the puppeteer module
     * path can each be pointed at a copy the PHP user can run.
     */
    protected function browsershot(string $url): Browsershot
    {
        $browsershot = Browsershot::url($url)
            ->newHeadless()
            ->noSandbox();

        if (!empty(config('app.chrome_path'))) {
            $browsershot->setChromePath(config('app.chrome_path'));
        }

        if (!empty(config('app.browsershot.node_binary'))) {
            $browsershot->setNodeBinary(config('app.browsershot.node_binary'));
        }

        if (!empty(config('app.browsershot.npm_binary'))) {
            $browsershot->setNpmBinary(config('app.browsershot.npm_binary'));
        }

        if (!empty(config('app.browsershot.node_module_path'))) {
            $browsershot->setNodeModulePath(config('app.browsershot.node_module_path'));
        }

        return $browsershot;
    }
}
