<?php

namespace Tests\Feature;

use App\Http\Controllers\EventAttendeesController;
use App\Jobs\GenerateTicket;
use App\Mail\EventBooked;
use App\Mailers\AttendeeMailer;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as Events;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Mime\Email;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * Ticket PDFs are made by Browsershot, which fails on the server. A missing PDF must not stop
 * the email, and must not undo an invited attendee.
 */
class TicketPdfFallbackTest extends TestCase
{
    use BootsWithoutDatabase;

    private const LEGACY_CHROME = '/home/ubuntu/.cache/puppeteer/chrome/linux-145.0.7632.67/chrome-linux64/chrome';

    private string $publicDir;

    /** @var Email[] */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gwe-ticket-pdf-'.bin2hex(random_bytes(4));
        mkdir($this->publicDir.'/user_content/pdf_tickets', 0777, true);
        $this->app->usePublicPath($this->publicDir);
        config(['attendize.event_pdf_tickets_path' => 'user_content/pdf_tickets', 'app.url' => 'https://events.example.test']);

        // Legacy Mail::send() is not recorded by Mail::fake(): keep the array mailer and watch it.
        $this->assertInstanceOf(ArrayTransport::class, $this->app['mailer']->getSymfonyTransport());
        Events::listen(MessageSending::class, function (MessageSending $event) {
            $this->sent[] = $event->message;
        });
    }

    protected function tearDown(): void
    {
        foreach (glob($this->publicDir.'/user_content/pdf_tickets/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->publicDir.'/user_content/pdf_tickets');
        @rmdir($this->publicDir.'/user_content');
        @rmdir($this->publicDir);

        parent::tearDown();
    }

    private function attendee(): Attendee
    {
        $event = (new Event)->forceFill(['id' => 4237, 'title' => 'Mastermind & Networking']);
        $order = (new Order)->forceFill(['id' => 77, 'order_reference' => 'AbCdE2710']);
        $order->setRelation('event', $event);

        $attendee = (new Attendee)->forceFill([
            'first_name' => 'Fixture',
            'last_name' => 'Guest',
            'email' => 'guest@example.test',
            'event_id' => 4237,
            'reference_index' => 1,
        ]);
        $attendee->setRelation('order', $order);

        return $attendee;
    }

    private function writePdf(string $reference): void
    {
        file_put_contents($this->publicDir.'/user_content/pdf_tickets/'.$reference.'.pdf', '%PDF-1.4 fixture');
    }

    private function onlySent(): Email
    {
        $this->assertCount(1, $this->sent, 'exactly one email');

        return $this->sent[0];
    }

    // ---- The mailers ----------------------------------------------------------------------

    public function test_an_invite_with_no_pdf_is_sent_without_an_attachment_and_the_miss_is_logged(): void
    {
        Log::spy();

        (new AttendeeMailer)->SendAttendeeInvite($this->attendee());

        $email = $this->onlySent();
        $this->assertSame('guest@example.test', $email->getTo()[0]->getAddress());
        $this->assertSame('Your ticket for the event Mastermind & Networking', $email->getSubject());
        $this->assertSame([], $email->getAttachments());

        $html = $email->getHtmlBody();
        $this->assertStringNotContainsString('attached', $html);
        $this->assertStringContainsString('You have been invited to the event', $html);
        $this->assertStringContainsString('Your order reference is <b>AbCdE2710</b>', $html);
        $this->assertStringContainsString('give your name or this reference', $html);
        $this->assertStringContainsString(route('showOrderDetails', ['order_reference' => 'AbCdE2710']), $html);

        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Ticket PDF missing: sending the ticket email without it'
            && $context === ['attendee_reference' => 'AbCdE2710-1', 'event_id' => 4237])->once();
    }

    public function test_an_invite_with_a_pdf_attaches_it_as_before(): void
    {
        Log::spy();
        $this->writePdf('AbCdE2710-1');

        (new AttendeeMailer)->SendAttendeeInvite($this->attendee());

        $email = $this->onlySent();
        $attachments = $email->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertSame('AbCdE2710-1.pdf', $attachments[0]->getFilename());
        $this->assertSame('%PDF-1.4 fixture', $attachments[0]->getBody());
        $this->assertStringContainsString('Your ticket for the event is attached to this email.', $email->getHtmlBody());

        Log::shouldNotHaveReceived('error');
    }

    public function test_a_resent_ticket_with_no_pdf_is_sent_without_an_attachment(): void
    {
        Log::spy();

        (new AttendeeMailer)->sendAttendeeTicket($this->attendee());

        $email = $this->onlySent();
        $this->assertSame([], $email->getAttachments());
        $this->assertStringNotContainsString('attached', $email->getHtmlBody());
        $this->assertStringContainsString('Your order reference is <b>AbCdE2710</b>', $email->getHtmlBody());
        Log::shouldHaveReceived('error')->withArgs(fn ($message) => $message === 'Ticket PDF missing: sending the ticket email without it')->once();
    }

    public function test_a_resent_ticket_with_a_pdf_attaches_it_as_before(): void
    {
        $this->writePdf('AbCdE2710-1');

        (new AttendeeMailer)->sendAttendeeTicket($this->attendee());

        $email = $this->onlySent();
        $this->assertCount(1, $email->getAttachments());
        $this->assertStringContainsString('is attached to this email.', $email->getHtmlBody());
    }

    // ---- The order confirmation -----------------------------------------------------------

    private function bookedOrder(): Order
    {
        $event = (new Event)->forceFill(['id' => 4237, 'title' => 'Mastermind & Networking', 'club' => null]);
        $event->setRelation('organiser', (new Organiser)->forceFill(['charge_tax' => 0, 'tax_value' => 0]));

        $order = (new Order)->forceFill([
            'id' => 77, 'order_reference' => 'AbCdE2710', 'email' => 'buyer@example.test',
            'first_name' => 'Fixture', 'last_name' => 'Buyer', 'amount' => 30, 'organiser_booking_fee' => 0,
        ]);
        $order->setRelation('event', $event);

        return $order;
    }

    public function test_the_booking_confirmation_goes_without_its_pdf_when_the_pdf_fails(): void
    {
        Log::spy();
        $this->app->bind('dompdf.wrapper', fn () => throw new RuntimeException('dompdf could not write'));

        $mail = (new EventBooked($this->bookedOrder()))->build();

        $this->assertSame([], $mail->attachments);
        $this->assertTrue($mail->hasTo('buyer@example.test'));
        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Booking PDF could not be generated: sending the confirmation without it'
            && $context['order_reference'] === 'AbCdE2710' && $context['message'] === 'dompdf could not write')->once();
    }

    public function test_the_booking_confirmation_attaches_its_pdf_as_before(): void
    {
        $this->app->bind('dompdf.wrapper', fn () => new class
        {
            public function loadView($view, $data)
            {
                return $this;
            }

            public function save($path)
            {
                file_put_contents($path, '%PDF-1.4 receipt');

                return $this;
            }
        });

        $mail = (new EventBooked($this->bookedOrder()))->build();

        $this->assertCount(1, $mail->attachments);
        $this->assertSame('receipt-10000077.pdf', $mail->attachments[0]['options']['as']);
    }

    // ---- Generating the PDF ---------------------------------------------------------------

    public function test_a_browsershot_failure_is_logged_with_the_ticket_reference(): void
    {
        Log::spy();

        $failing = new class('https://events.example.test') extends Browsershot
        {
            public function save(string $targetPath): void
            {
                throw new RuntimeException('Failed to launch the browser process! spawn /home/ubuntu/chrome EACCES');
            }
        };

        $job = new class('AbCdE2710-1', $failing) extends GenerateTicket
        {
            public function __construct($reference, private Browsershot $fake)
            {
                parent::__construct($reference);
            }

            protected function browsershot(string $url): Browsershot
            {
                return $this->fake;
            }
        };

        $job->handle();

        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Ticket PDF could not be generated'
            && $context['reference'] === 'AbCdE2710-1'
            && $context['order_reference'] === 'AbCdE2710'
            && $context['attendee_reference_index'] === '1'
            && $context['error'] === 'RuntimeException'
            && $context['message'] === 'Failed to launch the browser process! spawn /home/ubuntu/chrome EACCES')->once();
        $this->assertFileDoesNotExist($this->publicDir.'/user_content/pdf_tickets/AbCdE2710-1.pdf');
    }

    public function test_chrome_node_and_module_paths_come_from_config(): void
    {
        $this->assertSame(self::LEGACY_CHROME, config('app.chrome_path'), 'unchanged until the server is fixed');

        config([
            'app.chrome_path' => '/home/forge/.cache/puppeteer/chrome/chrome',
            'app.browsershot.node_binary' => '/usr/bin/node',
            'app.browsershot.npm_binary' => '/usr/bin/npm',
            'app.browsershot.node_module_path' => '/home/forge/node_modules',
        ]);

        $browsershot = (new \ReflectionMethod(GenerateTicket::class, 'browsershot'))
            ->invoke(new GenerateTicket('AbCdE2710'), 'https://events.example.test/order/AbCdE2710/tickets');

        $this->assertSame('/home/forge/.cache/puppeteer/chrome/chrome', $browsershot->createPdfCommand()['options']['executablePath']);
        foreach (['nodeBinary' => '/usr/bin/node', 'npmBinary' => '/usr/bin/npm', 'nodeModulePath' => '/home/forge/node_modules'] as $property => $value) {
            $this->assertSame($value, (new \ReflectionProperty(Browsershot::class, $property))->getValue($browsershot), $property);
        }
    }

    public function test_unset_node_paths_keep_browsershots_defaults(): void
    {
        $browsershot = (new \ReflectionMethod(GenerateTicket::class, 'browsershot'))
            ->invoke(new GenerateTicket('AbCdE2710'), 'https://events.example.test/order/AbCdE2710/tickets');

        $this->assertSame(self::LEGACY_CHROME, $browsershot->createPdfCommand()['options']['executablePath']);
        foreach (['nodeBinary', 'npmBinary', 'nodeModulePath'] as $property) {
            $this->assertNull((new \ReflectionProperty(Browsershot::class, $property))->getValue($browsershot), $property);
        }
    }

    // ---- Invite Attendee ------------------------------------------------------------------

    private function buildInviteSchema(): void
    {
        config([
            'database.connections.events_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'events_test',
        ]);
        DB::purge('events_test');

        $schema = Schema::connection('events_test');

        $schema->create('organisers', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->boolean('charge_tax')->default(false);
            $t->decimal('tax_value')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        $schema->create('events', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('account_id');
            $t->unsignedInteger('organiser_id');
            $t->string('title');
            $t->string('club')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        $schema->create('tickets', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('account_id');
            $t->unsignedInteger('event_id');
            $t->string('title');
            $t->integer('quantity_sold')->default(0);
            $t->decimal('sales_volume')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        $schema->create('orders', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('account_id');
            $t->unsignedInteger('event_id');
            $t->string('order_reference');
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('email')->nullable();
            $t->unsignedInteger('order_status_id')->nullable();
            $t->decimal('amount')->default(0);
            $t->decimal('taxamt')->default(0);
            $t->boolean('is_payment_received')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
        $schema->create('order_items', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('order_id');
            $t->string('title');
            $t->integer('quantity');
            $t->decimal('unit_price');
        });
        $schema->create('event_stats', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('event_id');
            $t->date('date');
            $t->integer('tickets_sold')->default(0);
        });
        $schema->create('attendees', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('account_id');
            $t->unsignedInteger('event_id');
            $t->unsignedInteger('order_id');
            $t->unsignedInteger('ticket_id');
            $t->string('first_name');
            $t->string('last_name')->nullable();
            $t->string('email');
            $t->integer('reference_index');
            $t->string('private_reference_number');
            $t->boolean('is_cancelled')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('organisers')->insert(['id' => 1, 'name' => 'Global Woman']);
        DB::table('events')->insert(['id' => 4237, 'account_id' => 1, 'organiser_id' => 1, 'title' => 'Mastermind & Networking']);
        DB::table('tickets')->insert(['id' => 9, 'account_id' => 1, 'event_id' => 4237, 'title' => 'Guest']);

        Auth::setUser((new User)->forceFill(['id' => 5, 'account_id' => 1]));
    }

    private function invite(): array
    {
        $request = Request::create('/event/4237/attendees/invite', 'POST', [
            'first_name' => 'Fixture',
            'last_name' => 'Guest',
            'email' => 'guest@example.test',
            'ticket_id' => 9,
            'email_ticket' => '1',
        ]);
        $this->app->instance('request', $request);

        return $this->app->make(EventAttendeesController::class)->postInviteAttendee($request, 4237)->getData(true);
    }

    public function test_invite_with_email_ticket_keeps_the_attendee_and_sends_the_email_without_a_pdf(): void
    {
        $this->buildInviteSchema();
        // GenerateTicket runs but makes no PDF, as on the server today.
        Bus::fake([GenerateTicket::class]);
        Log::spy();

        $response = $this->invite();

        $this->assertSame('success', $response['status']);
        $this->assertSame(1, DB::table('attendees')->where('email', 'guest@example.test')->count(), 'the attendee was not rolled back');
        $this->assertSame(1, DB::table('orders')->where('event_id', 4237)->count());
        Bus::assertDispatchedSync(GenerateTicket::class);

        $email = $this->onlySent();
        $this->assertSame('guest@example.test', $email->getTo()[0]->getAddress());
        $this->assertSame([], $email->getAttachments());
        $this->assertStringContainsString('You have been invited to the event', $email->getHtmlBody());
        $this->assertSame(trans('Controllers.attendee_successfully_invited'), session('message'));
    }

    public function test_invite_keeps_the_attendee_when_the_email_itself_fails(): void
    {
        $this->buildInviteSchema();
        Bus::fake([GenerateTicket::class]);
        Log::spy();
        Events::listen(MessageSending::class, fn () => throw new RuntimeException('SES rejected the message'));

        $response = $this->invite();

        $this->assertSame('success', $response['status']);
        $this->assertSame(1, DB::table('attendees')->where('email', 'guest@example.test')->count(), 'the attendee was not rolled back');
        $this->assertSame('Attendee added, but the invitation email could not be sent.', session('message'));
        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context = []) => $message === 'Invite email could not be sent; the attendee was added'
            && $context['event_id'] === 4237 && $context['message'] === 'SES rejected the message')->once();
    }
}
