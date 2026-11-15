<?php

namespace App\Support\Legacy;

use App\Enums\OrderStatus;
use App\Models\Answer;
use App\Models\Attendee;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organiser;
use App\Models\Question;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reads the read-only Attendize databases (`legacy`, `legacy_lounge`) and
 * upserts the rows into the new schema, keyed by the `legacy_id` columns.
 *
 * Every entity import is wrapped in a transaction by the calling command, and
 * each row is matched on its legacy id (or email/reference for rows that have
 * no legacy id) so the import can be re-run safely.
 */
class LegacyImporter
{
    public const ENTITIES = [
        'users',
        'organisers',
        'events',
        'tickets',
        'orders',
        'attendees',
        'questions',
        'answers',
        'discount_codes',
    ];

    /** @var array<string, array{created: int, updated: int, skipped: int}> */
    private array $counts = [];

    /** @var list<string> */
    private array $warnings = [];

    /** @var array<int, int> legacy account id => user id */
    private array $userMap = [];

    /** @var array<int, array{id: int, currency: string}> legacy organiser id => meta */
    private array $organiserMeta = [];

    /** @var array<int, array{id: int, currency: string, timezone: string, organiser_id: int}> */
    private array $eventMeta = [];

    /** @var array<int, int> legacy ticket id => ticket id */
    private array $ticketMap = [];

    /** @var array<int, int> legacy order id => order id */
    private array $orderMap = [];

    /** @var array<int, int> legacy attendee id => attendee id */
    private array $attendeeMap = [];

    /** @var array<string, int> "legacyEventId:legacyQuestionId" => question id */
    private array $questionMap = [];

    /** @var array<int, string> legacy currency id => code */
    private array $currencyCodes = [];

    /** @var array<int, string> legacy timezone id => name */
    private array $timezones = [];

    /** @var array<string, string|null> legacy file path => stored path */
    private array $fileCache = [];

    /** @var array<int, string> legacy event id => image path */
    private array $eventImages = [];

    /** @var array<string, true> */
    private array $usedEventSlugs = [];

    /** @var array<string, true> */
    private array $usedOrderReferences = [];

    /** @var array<string, true> */
    private array $usedAttendeeReferences = [];

    private int $syntheticIds = 0;

    public function __construct(
        private readonly bool $dryRun = false,
        private readonly ?int $limit = null,
        private readonly ?Closure $logger = null,
    ) {
        foreach (self::ENTITIES as $entity) {
            $this->counts[$entity] = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }
    }

    /**
     * Expand the `--only` option into canonical entity names, keeping the
     * import order defined in ENTITIES.
     *
     * @param  list<string>  $only
     * @return list<string>
     */
    public static function normaliseOnly(array $only): array
    {
        $aliases = [
            'accounts' => 'users',
            'account' => 'users',
            'users' => 'users',
            'user' => 'users',
            'organisers' => 'organisers',
            'organiser' => 'organisers',
            'organizers' => 'organisers',
            'events' => 'events',
            'event' => 'events',
            'tickets' => 'tickets',
            'ticket' => 'tickets',
            'orders' => 'orders',
            'order' => 'orders',
            'order_items' => 'orders',
            'attendees' => 'attendees',
            'attendee' => 'attendees',
            'questions' => 'questions',
            'question' => 'questions',
            'question_options' => 'questions',
            'answers' => 'answers',
            'answer' => 'answers',
            'question_answers' => 'answers',
            'discount_codes' => 'discount_codes',
            'discount_code' => 'discount_codes',
            'access_codes' => 'discount_codes',
            'event_access_codes' => 'discount_codes',
        ];

        $requested = [];

        foreach ($only as $value) {
            foreach (explode(',', (string) $value) as $piece) {
                $key = strtolower(trim($piece));

                if ($key === '') {
                    continue;
                }

                if (! isset($aliases[$key])) {
                    throw new \InvalidArgumentException("Unknown entity [{$key}] for --only.");
                }

                $requested[$aliases[$key]] = true;
            }
        }

        if ($requested === []) {
            return self::ENTITIES;
        }

        return array_values(array_filter(
            self::ENTITIES,
            fn (string $entity) => isset($requested[$entity]),
        ));
    }

    public function run(array $entities): void
    {
        $entities = $entities === [] ? self::ENTITIES : $entities;

        $steps = [
            'users' => 'importUsers',
            'organisers' => 'importOrganisers',
            'events' => 'importEvents',
            'tickets' => 'importTickets',
            'orders' => 'importOrders',
            'attendees' => 'importAttendees',
            'questions' => 'importQuestions',
            'answers' => 'importAnswers',
            'discount_codes' => 'importDiscountCodes',
        ];

        foreach ($steps as $entity => $method) {
            if (! in_array($entity, $entities, true)) {
                continue;
            }

            $this->log(($this->dryRun ? 'Dry run: ' : '')."importing {$entity}...");

            if ($this->dryRun) {
                $this->{$method}();
            } else {
                DB::transaction(fn () => $this->{$method}());
            }

            $counts = $this->counts[$entity];
            $this->log(sprintf(
                '  %s: %d created, %d updated, %d skipped',
                $entity,
                $counts['created'],
                $counts['updated'],
                $counts['skipped'],
            ));
        }

        $this->coverageWarnings($entities);
    }

    /**
     * @return array<string, array{created: int, updated: int, skipped: int}>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    // -----------------------------------------------------------------
    // Entities
    // -----------------------------------------------------------------

    private function importUsers(): void
    {
        $passwords = $this->legacy()
            ->table('users')
            ->whereNull('deleted_at')
            ->whereNotNull('password')
            ->orderByDesc('is_parent')
            ->get(['account_id', 'password'])
            ->groupBy('account_id')
            ->map(fn ($rows) => (string) $rows->first()->password);

        $query = $this->legacy()
            ->table('accounts')
            ->select(['id', 'first_name', 'last_name', 'email', 'currency_id', 'timezone_id'])
            ->whereNull('deleted_at')
            ->orderBy('id');

        foreach ($this->rows($query) as $row) {
            $email = trim((string) $row->email);

            if ($email === '') {
                $this->skip('users', "account {$row->id} has no email");

                continue;
            }

            $existing = User::query()->where('email', $email)->first();

            if ($existing !== null) {
                $this->userMap[(int) $row->id] = (int) $existing->getKey();
                $this->counts['users']['skipped']++;

                continue;
            }

            $name = trim(trim((string) $row->first_name).' '.trim((string) $row->last_name));

            if ($name === '') {
                $name = Str::before($email, '@');
            }

            $password = $passwords->get($row->id);

            if ($password === null || $password === '') {
                $this->warn("account {$row->id} ({$email}) had no legacy password; a random one was set.");
                $password = Str::random(40);
            }

            $id = $this->create('users', fn () => User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]));

            $this->userMap[(int) $row->id] = $id;
        }
    }

    private function importOrganisers(): void
    {
        $query = $this->legacy()
            ->table('organisers as o')
            ->leftJoin('accounts as a', 'a.id', '=', 'o.account_id')
            ->select([
                'o.id',
                'o.account_id',
                'o.name',
                'o.email',
                'o.phone',
                'o.about',
                'o.logo_path',
                'a.currency_id',
                'a.timezone_id',
                'a.deleted_at as account_deleted_at',
            ])
            ->whereNull('o.deleted_at')
            ->orderBy('o.id');

        foreach ($this->rows($query) as $row) {
            $userId = $this->userIdFor((int) $row->account_id);

            if ($userId === null) {
                $this->skip('organisers', "organiser {$row->id} skipped: account {$row->account_id} was not imported");

                continue;
            }

            $currency = $this->currencyCode($row->currency_id);
            $timezone = $this->timezoneName($row->timezone_id);
            $logo = $this->copyLegacyFile($row->logo_path, 'organisers');

            $attributes = [
                'user_id' => $userId,
                'name' => trim((string) $row->name) ?: 'Organiser '.$row->id,
                'email' => trim((string) $row->email),
                'phone' => $this->nullIfBlank($row->phone),
                'about' => $this->nullIfBlank($row->about),
                'logo_path' => $logo,
                'currency_code' => $currency,
                'timezone' => $timezone,
                'legacy_id' => (int) $row->id,
            ];

            $existing = Organiser::withTrashed()->where('legacy_id', $row->id)->first();

            if ($existing !== null) {
                $id = (int) $existing->getKey();
                $this->update('organisers', $existing, $attributes);
            } else {
                $id = $this->create('organisers', fn () => Organiser::create($attributes + [
                    'slug' => $this->uniqueOrganiserSlug($attributes['name']),
                ]));
            }

            $this->organiserMeta[(int) $row->id] = ['id' => $id, 'currency' => $currency];
        }

        $this->importClubStripeAccounts();
    }

    /**
     * `clubs` lives in the lounge database and holds the Stripe Connect account
     * for each club; match them onto organisers by slug/name.
     */
    private function importClubStripeAccounts(): void
    {
        try {
            $clubs = DB::connection('legacy_lounge')
                ->table('clubs')
                ->whereNotNull('stripe_account_id')
                ->where('stripe_account_id', '<>', '')
                ->orderBy('id')
                ->get(['id', 'title', 'slug', 'stripe_account_id', 'is_onboarded', 'onboarded_at']);
        } catch (Throwable $e) {
            $this->warn('legacy_lounge is unavailable, club Stripe accounts were not imported: '.$e->getMessage());

            return;
        }

        $organisers = Organiser::query()->get(['id', 'name', 'slug', 'legacy_id', 'stripe_account_id']);

        foreach ($clubs as $club) {
            $slug = Str::lower(trim((string) $club->slug));
            $title = Str::lower(trim((string) $club->title));

            $match = $organisers->first(function (Organiser $organiser) use ($slug, $title) {
                $name = Str::lower(trim((string) $organiser->name));

                return ($slug !== '' && Str::lower($organiser->slug) === $slug)
                    || ($title !== '' && $name === $title)
                    || ($slug !== '' && Str::slug($name) === $slug);
            });

            if ($match === null) {
                $this->warnUnmatchedClub($club->slug ?: $club->title ?: (string) $club->id);

                continue;
            }

            if ($this->dryRun) {
                $this->counts['organisers']['updated']++;

                continue;
            }

            $match->forceFill([
                'stripe_account_id' => $club->stripe_account_id,
                'stripe_charges_enabled' => true,
                'stripe_details_submitted' => true,
                'stripe_onboarded_at' => $club->onboarded_at ? Carbon::parse($club->onboarded_at) : now(),
            ])->save();

            $this->counts['organisers']['updated']++;
        }
    }

    private function importEvents(): void
    {
        $this->eventImages = $this->legacy()
            ->table('event_images')
            ->orderBy('id')
            ->get(['event_id', 'image_path'])
            ->groupBy('event_id')
            ->map(fn ($rows) => (string) $rows->first()->image_path)
            ->all();

        $columns = [
            'id', 'slug', 'title', 'description', 'short_description', 'about_event',
            'start_date', 'end_date', 'event_timezone', 'currency_id', 'organiser_id',
            'venue_name', 'location_address', 'location_address_line_1', 'location_state',
            'location_post_code', 'location_country', 'location_country_code',
            'location_lat', 'location_long', 'bg_image_path', 'is_live',
        ];

        if ($this->legacyHasColumn('events', 'is_public')) {
            $columns[] = 'is_public';
        }

        $query = $this->legacy()
            ->table('events')
            ->select($columns)
            ->whereNull('deleted_at')
            ->orderBy('id');

        foreach ($this->rows($query) as $row) {
            $organiser = $this->organiserMetaFor((int) $row->organiser_id);

            if ($organiser === null) {
                $this->skip('events', "event {$row->id} skipped: organiser {$row->organiser_id} was not imported");

                continue;
            }

            $timezone = $this->resolveEventTimezone($row->event_timezone, (int) $organiser['id']);
            $currency = $this->currencyCode($row->currency_id, $organiser['currency']);
            $start = $this->toUtc($row->start_date, $timezone);

            if ($start === null) {
                $this->skip('events', "event {$row->id} skipped: it has no usable start_date");

                continue;
            }

            $existing = Event::withTrashed()->where('legacy_id', $row->id)->first();

            $attributes = [
                'organiser_id' => (int) $organiser['id'],
                'title' => trim((string) $row->title) ?: 'Event '.$row->id,
                'description' => $this->nullIfBlank($row->description) ?? $this->nullIfBlank($row->short_description),
                'cover_image_path' => $this->eventCoverImage($existing, $row),
                'start_date' => $start,
                'end_date' => $this->toUtc($row->end_date, $timezone),
                'timezone' => $timezone,
                'venue_name' => $this->nullIfBlank($row->venue_name),
                'address1' => $this->nullIfBlank($row->location_address_line_1) ?? $this->nullIfBlank($row->location_address),
                'state' => $this->nullIfBlank($row->location_state),
                'postal_code' => $this->nullIfBlank($row->location_post_code),
                'country_code' => $this->countryCode($row->location_country_code, $row->location_country),
                'latitude' => $this->coordinate($row->location_lat),
                'longitude' => $this->coordinate($row->location_long),
                'currency_code' => $currency,
                'status' => StatusMapper::eventStatus($row->is_live),
                'is_public' => isset($row->is_public) ? (bool) $row->is_public : true,
                'legacy_id' => (int) $row->id,
            ];

            if ($existing !== null) {
                $id = (int) $existing->getKey();
                $this->update('events', $existing, $attributes);
            } else {
                $id = $this->create('events', fn () => Event::create($attributes + [
                    'slug' => $this->uniqueEventSlug((int) $organiser['id'], $row->slug, $attributes['title']),
                ]));
            }

            $this->eventMeta[(int) $row->id] = [
                'id' => $id,
                'currency' => $currency,
                'timezone' => $timezone,
                'organiser_id' => (int) $organiser['id'],
            ];
        }
    }

    private function importTickets(): void
    {
        $soldCounts = $this->legacy()
            ->table('attendees')
            ->whereNull('deleted_at')
            ->selectRaw('ticket_id, count(*) as aggregate')
            ->groupBy('ticket_id')
            ->pluck('aggregate', 'ticket_id')
            ->all();

        $query = $this->legacy()
            ->table('tickets')
            ->select([
                'id', 'event_id', 'title', 'description', 'price', 'quantity_available',
                'quantity_sold', 'min_per_person', 'max_per_person', 'start_sale_date',
                'end_sale_date', 'is_hidden', 'sort_order',
            ])
            ->whereNull('deleted_at')
            ->orderBy('id');

        foreach ($this->rows($query) as $row) {
            $event = $this->eventMetaFor((int) $row->event_id);

            if ($event === null) {
                $this->skip('tickets', "ticket {$row->id} skipped: event {$row->event_id} was not imported");

                continue;
            }

            $quantityAvailable = (int) ($row->quantity_available ?? 0);
            $legacySold = (int) ($row->quantity_sold ?? 0);
            $sold = $legacySold > 0 ? $legacySold : (int) ($soldCounts[$row->id] ?? 0);

            $attributes = [
                'event_id' => (int) $event['id'],
                'title' => trim((string) $row->title) ?: 'Ticket '.$row->id,
                'description' => $this->nullIfBlank($row->description),
                'price' => StatusMapper::priceToMinor($row->price, $event['currency']),
                'quantity_available' => $quantityAvailable > 0 ? $quantityAvailable : null,
                'quantity_sold' => $sold,
                'min_per_order' => max(1, (int) ($row->min_per_person ?? 0)),
                'max_per_order' => max(1, (int) ($row->max_per_person ?? 0) ?: 10),
                'sales_start_at' => $this->toUtc($row->start_sale_date, $event['timezone']),
                'sales_end_at' => $this->toUtc($row->end_sale_date, $event['timezone']),
                'is_hidden' => (bool) $row->is_hidden,
                'sort_order' => max(0, (int) ($row->sort_order ?? 0)),
                'legacy_id' => (int) $row->id,
            ];

            $existing = Ticket::query()->where('legacy_id', $row->id)->first();

            $id = $existing === null
                ? $this->create('tickets', fn () => Ticket::create($attributes))
                : tap((int) $existing->getKey(), fn (int $id) => $this->update('tickets', $existing, $attributes));

            $this->ticketMap[(int) $row->id] = $id;
        }
    }

    private function importOrders(): void
    {
        $itemsByOrder = $this->legacy()
            ->table('order_items')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['order_id', 'title', 'unit_price', 'quantity'])
            ->groupBy('order_id');

        $statusNames = $this->legacy()->table('order_statuses')->pluck('name', 'id')->all();

        $columns = [
            'id', 'event_id', 'order_status_id', 'first_name', 'last_name', 'email',
            'order_reference', 'amount', 'discount', 'booking_fee', 'organiser_booking_fee',
            'taxamt', 'amount_refunded', 'order_date', 'created_at', 'payment_intent',
            'stripe_account_id',
        ];

        if ($this->legacyHasColumn('orders', 'is_payment_received')) {
            $columns[] = 'is_payment_received';
        }

        $query = $this->legacy()
            ->table('orders')
            ->select($columns)
            ->whereNull('deleted_at')
            ->orderBy('id');

        foreach ($this->rows($query) as $row) {
            $event = $this->eventMetaFor((int) $row->event_id);

            if ($event === null) {
                $this->skip('orders', "order {$row->id} skipped: event {$row->event_id} was not imported");

                continue;
            }

            $legacyStatusName = $statusNames[$row->order_status_id] ?? null;
            $paymentReceived = isset($row->is_payment_received)
                ? (bool) $row->is_payment_received
                : str_contains(Str::lower((string) $legacyStatusName), 'complete');
            $status = StatusMapper::orderStatus($legacyStatusName, $paymentReceived);

            $items = $itemsByOrder->get($row->id) ?? collect();
            $itemsSubtotal = 0;

            foreach ($items as $item) {
                $itemsSubtotal += StatusMapper::priceToMinor($item->unit_price, $event['currency'])
                    * max(0, (int) $item->quantity);
            }

            $total = StatusMapper::priceToMinor($row->amount, $event['currency']);
            $discount = StatusMapper::priceToMinor($row->discount, $event['currency']);
            $tax = StatusMapper::priceToMinor($row->taxamt ?? 0, $event['currency']);
            $subtotal = $itemsSubtotal > 0 ? $itemsSubtotal : max(0, $total + $discount);
            $paidAt = $this->legacyTimestamp($row->order_date ?? null, $row->created_at ?? null);

            $email = trim((string) $row->email);

            if ($email === '') {
                $email = "legacy-order-{$row->id}@import.invalid";
                $this->warn("order {$row->id} had no email, placeholder {$email} used.");
            }

            $attributes = [
                'event_id' => (int) $event['id'],
                'organiser_id' => (int) $event['organiser_id'],
                'status' => $status,
                'first_name' => $this->nullIfBlank($row->first_name),
                'last_name' => $this->nullIfBlank($row->last_name),
                'email' => $email,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'total' => $total,
                'currency_code' => $event['currency'],
                'stripe_payment_intent_id' => $this->paymentIntent($row),
                'stripe_account_id' => $this->nullIfBlank($row->stripe_account_id),
                'refunded_amount' => StatusMapper::priceToMinor($row->amount_refunded ?? 0, $event['currency']),
                'paid_at' => $status->isPaid() ? $paidAt : null,
                'refunded_at' => $status === OrderStatus::Refunded ? $paidAt : null,
                'cancelled_at' => $status === OrderStatus::Cancelled ? $paidAt : null,
                'legacy_id' => (int) $row->id,
            ];

            $existing = Order::query()->where('legacy_id', $row->id)->first();

            if ($existing !== null) {
                $id = (int) $existing->getKey();
                $this->update('orders', $existing, $attributes);
            } else {
                $id = $this->create('orders', fn () => Order::create($attributes + [
                    'reference' => $this->uniqueOrderReference($row->order_reference),
                ]));
            }

            $this->orderMap[(int) $row->id] = $id;

            $this->importOrderItems($id, (int) $row->event_id, $event, $items);
        }
    }

    private function importOrderItems(int $orderId, int $legacyEventId, array $event, $items): void
    {
        if ($this->dryRun) {
            return;
        }

        OrderItem::query()->where('order_id', $orderId)->delete();

        foreach ($items as $item) {
            $unitPrice = StatusMapper::priceToMinor($item->unit_price, $event['currency']);
            $quantity = max(0, (int) $item->quantity);

            OrderItem::create([
                'order_id' => $orderId,
                'ticket_id' => $this->ticketIdByTitle($legacyEventId, (string) $item->title),
                'description' => trim((string) $item->title) ?: 'Ticket',
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'total' => $unitPrice * $quantity,
            ]);
        }
    }

    private function importAttendees(): void
    {
        $columns = [
            'id', 'order_id', 'event_id', 'ticket_id', 'first_name', 'last_name', 'email',
            'is_cancelled', 'is_refunded', 'has_arrived', 'arrival_time',
        ];

        if ($this->legacyHasColumn('attendees', 'private_reference_number')) {
            $columns[] = 'private_reference_number';
        }

        $query = $this->legacy()
            ->table('attendees')
            ->select($columns)
            ->whereNull('deleted_at')
            ->orderBy('id');

        foreach ($this->rows($query) as $row) {
            $orderId = $this->orderIdFor((int) $row->order_id);
            $event = $this->eventMetaFor((int) $row->event_id);

            if ($orderId === null || $event === null) {
                $this->skip('attendees', "attendee {$row->id} skipped: order {$row->order_id} / event {$row->event_id} was not imported");

                continue;
            }

            if (Attendee::withTrashed()->where('legacy_id', $row->id)->exists()) {
                $existing = Attendee::withTrashed()->where('legacy_id', $row->id)->first();
                $this->attendeeMap[(int) $row->id] = (int) $existing->getKey();
                $this->counts['attendees']['skipped']++;

                continue;
            }

            $attributes = [
                'order_id' => $orderId,
                'event_id' => (int) $event['id'],
                'ticket_id' => $this->ticketIdFor((int) $row->ticket_id),
                'first_name' => $this->nullIfBlank($row->first_name),
                'last_name' => $this->nullIfBlank($row->last_name),
                'email' => $this->nullIfBlank($row->email),
                'reference' => $this->attendeeReference($row->private_reference_number ?? null),
                'is_cancelled' => (bool) $row->is_cancelled,
                'is_refunded' => (bool) ($row->is_refunded ?? false),
                'checked_in_at' => (bool) $row->has_arrived
                    ? $this->toUtc($row->arrival_time, $event['timezone'])
                    : null,
                'legacy_id' => (int) $row->id,
            ];

            $id = $this->create('attendees', fn () => Attendee::create($attributes));

            $this->attendeeMap[(int) $row->id] = $id;
        }
    }

    private function importQuestions(): void
    {
        $query = $this->legacy()
            ->table('questions as q')
            ->leftJoin('question_types as t', 't.id', '=', 'q.question_type_id')
            ->select([
                'q.id', 'q.title', 'q.is_required', 't.alias', 't.has_options', 't.allow_multiple',
            ])
            ->whereNull('q.deleted_at')
            ->orderBy('q.id');

        if ($this->legacyHasColumn('questions', 'is_enabled')) {
            $query->where('q.is_enabled', 1);
        }

        if ($this->legacyHasColumn('questions', 'sort_order')) {
            $query->addSelect('q.sort_order');
        }

        $questions = $this->rows($query)->keyBy('id');
        $options = $this->legacy()
            ->table('question_options')
            ->orderBy('id')
            ->get(['question_id', 'name'])
            ->groupBy('question_id');

        $pivot = $this->legacy()->table('event_question')->get(['event_id', 'question_id']);
        $used = 0;

        foreach ($pivot as $link) {
            if ($this->limit !== null && $used >= $this->limit) {
                break;
            }

            $question = $questions->get($link->question_id);
            $event = $this->eventMetaFor((int) $link->event_id);

            if ($question === null || $event === null) {
                $this->skip('questions', "question {$link->question_id} skipped for event {$link->event_id}: missing question or event");

                continue;
            }

            $optionNames = ($options->get($question->id) ?? collect())->pluck('name')->values()->all();
            $type = StatusMapper::questionType(
                $question->alias,
                (bool) ($question->has_options ?? false),
                (bool) ($question->allow_multiple ?? false),
            );

            $attributes = [
                'event_id' => (int) $event['id'],
                'title' => trim((string) $question->title) ?: 'Question '.$question->id,
                'type' => $type,
                'options' => $optionNames === [] ? null : $optionNames,
                'is_required' => (bool) $question->is_required,
                'applies_to' => 'attendee',
                'sort_order' => (int) ($question->sort_order ?? 0),
            ];

            $existing = Question::query()
                ->where('event_id', $attributes['event_id'])
                ->where('title', $attributes['title'])
                ->first();

            $id = $existing === null
                ? $this->create('questions', fn () => Question::create($attributes))
                : tap((int) $existing->getKey(), fn (int $id) => $this->update('questions', $existing, $attributes));

            $this->questionMap[$link->event_id.':'.$link->question_id] = $id;
            $used++;
        }
    }

    private function importAnswers(): void
    {
        $query = $this->legacy()
            ->table('question_answers')
            ->select(['id', 'attendee_id', 'event_id', 'question_id', 'answer_text'])
            ->orderBy('id');

        foreach ($this->rows($query) as $row) {
            $attendeeId = $this->attendeeIdFor((int) $row->attendee_id);
            $questionId = $this->questionMapFor((int) $row->event_id, (int) $row->question_id);

            if ($attendeeId === null || $questionId === null) {
                $this->skip('answers', "answer {$row->id} skipped: attendee {$row->attendee_id} / question {$row->question_id} was not imported");

                continue;
            }

            $attributes = [
                'attendee_id' => $attendeeId,
                'question_id' => $questionId,
                'answer_text' => $this->nullIfBlank($row->answer_text),
            ];

            $existing = Answer::query()
                ->where('attendee_id', $attendeeId)
                ->where('question_id', $questionId)
                ->first();

            if ($existing === null) {
                $this->create('answers', fn () => Answer::create($attributes));
            } else {
                $this->update('answers', $existing, $attributes);
            }
        }
    }

    private function importDiscountCodes(): void
    {
        $columns = ['id', 'event_id', 'code'];

        foreach (['usage_count', 'amounts', 'usage_requirements', 'quantity_available', 'expires_at'] as $optional) {
            if ($this->legacyHasColumn('event_access_codes', $optional)) {
                $columns[] = $optional;
            }
        }

        $query = $this->legacy()
            ->table('event_access_codes')
            ->select($columns)
            ->whereNull('deleted_at')
            ->orderBy('id');

        $seen = [];

        foreach ($this->rows($query) as $row) {
            $event = $this->eventMetaFor((int) $row->event_id);

            if ($event === null) {
                $this->skip('discount_codes', "access code {$row->id} skipped: event {$row->event_id} was not imported");

                continue;
            }

            $code = Str::upper(trim((string) $row->code));

            if ($code === '') {
                $this->skip('discount_codes', "access code {$row->id} has no code");

                continue;
            }

            $key = $event['id'].':'.$code;

            if (isset($seen[$key])) {
                $this->skip('discount_codes', "access code {$row->id} ({$code}) duplicated within event {$row->event_id}");
                $this->warn("event {$row->event_id}: duplicate access code {$code} skipped.");

                continue;
            }

            $seen[$key] = true;

            $discount = StatusMapper::discountFromAccessCode((array) $row, $event['currency']);
            $amount = $row->amounts ?? $row->amount ?? null;

            if ($amount === null && $this->legacyHasColumn('event_access_codes', 'amounts')) {
                $this->warn("access code {$code} (event {$row->event_id}) has no amounts; imported with a 0% discount.");
            }

            $attributes = [
                'event_id' => (int) $event['id'],
                'code' => $code,
                'type' => $discount['type'],
                'value' => $discount['value'],
                'quantity_available' => isset($row->quantity_available) && (int) $row->quantity_available > 0
                    ? (int) $row->quantity_available
                    : null,
                'quantity_used' => (int) ($row->usage_count ?? 0),
                'expires_at' => isset($row->expires_at) ? $this->toUtc($row->expires_at, $event['timezone']) : null,
                'is_active' => true,
                'legacy_id' => (int) $row->id,
            ];

            $existing = DiscountCode::query()->where('legacy_id', $row->id)->first();

            if ($existing === null) {
                $existing = DiscountCode::query()
                    ->where('event_id', $attributes['event_id'])
                    ->where('code', $code)
                    ->first();
            }

            if ($existing === null) {
                $this->create('discount_codes', fn () => DiscountCode::create($attributes));
            } else {
                $this->update('discount_codes', $existing, $attributes);
            }
        }
    }

    // -----------------------------------------------------------------
    // Coverage warnings
    // -----------------------------------------------------------------

    private function coverageWarnings(array $entities): void
    {
        if (in_array('tickets', $entities, true)) {
            $withoutTickets = $this->dryRun
                ? $this->legacyEventsWithoutTickets()
                : Event::query()->whereNotNull('legacy_id')->whereDoesntHave('tickets')->count();

            if ($withoutTickets > 0) {
                $this->warn("{$withoutTickets} imported event(s) have no tickets.");
            }
        }

        if (in_array('attendees', $entities, true)) {
            $withoutAttendees = $this->dryRun
                ? $this->legacyOrdersWithoutAttendees()
                : Order::query()->whereNotNull('legacy_id')->whereDoesntHave('attendees')->count();

            if ($withoutAttendees > 0) {
                $this->warn("{$withoutAttendees} imported order(s) have no attendees.");
            }
        }
    }

    private function legacyEventsWithoutTickets(): int
    {
        return $this->legacy()
            ->table('events')
            ->whereNull('deleted_at')
            ->whereNotExists(fn ($query) => $query->from('tickets')
                ->whereColumn('tickets.event_id', 'events.id')
                ->whereNull('tickets.deleted_at'))
            ->count();
    }

    private function legacyOrdersWithoutAttendees(): int
    {
        return $this->legacy()
            ->table('orders')
            ->whereNull('deleted_at')
            ->whereNotExists(fn ($query) => $query->from('attendees')
                ->whereColumn('attendees.order_id', 'orders.id')
                ->whereNull('attendees.deleted_at'))
            ->count();
    }

    // -----------------------------------------------------------------
    // Lookups
    // -----------------------------------------------------------------

    private function userIdFor(int $legacyAccountId): ?int
    {
        if (isset($this->userMap[$legacyAccountId])) {
            return $this->userMap[$legacyAccountId];
        }

        $id = User::query()
            ->whereIn('email', $this->legacy()
                ->table('accounts')
                ->where('id', $legacyAccountId)
                ->pluck('email'))
            ->value('id');

        return $id === null ? null : $this->userMap[$legacyAccountId] = (int) $id;
    }

    /**
     * @return array{id: int, currency: string}|null
     */
    private function organiserMetaFor(int $legacyOrganiserId): ?array
    {
        if (isset($this->organiserMeta[$legacyOrganiserId])) {
            return $this->organiserMeta[$legacyOrganiserId];
        }

        $organiser = Organiser::withTrashed()->where('legacy_id', $legacyOrganiserId)->first();

        if ($organiser === null) {
            return null;
        }

        return $this->organiserMeta[$legacyOrganiserId] = [
            'id' => (int) $organiser->getKey(),
            'currency' => $organiser->currency_code,
        ];
    }

    /**
     * @return array{id: int, currency: string, timezone: string, organiser_id: int}|null
     */
    private function eventMetaFor(int $legacyEventId): ?array
    {
        if (isset($this->eventMeta[$legacyEventId])) {
            return $this->eventMeta[$legacyEventId];
        }

        $event = Event::withTrashed()->where('legacy_id', $legacyEventId)->first();

        if ($event === null) {
            return null;
        }

        return $this->eventMeta[$legacyEventId] = [
            'id' => (int) $event->getKey(),
            'currency' => $event->currency_code,
            'timezone' => $event->timezone,
            'organiser_id' => (int) $event->organiser_id,
        ];
    }

    private function ticketIdFor(int $legacyTicketId): ?int
    {
        if (isset($this->ticketMap[$legacyTicketId])) {
            return $this->ticketMap[$legacyTicketId];
        }

        $id = Ticket::query()->where('legacy_id', $legacyTicketId)->value('id');

        return $id === null ? null : $this->ticketMap[$legacyTicketId] = (int) $id;
    }

    private function orderIdFor(int $legacyOrderId): ?int
    {
        if (isset($this->orderMap[$legacyOrderId])) {
            return $this->orderMap[$legacyOrderId];
        }

        $id = Order::query()->where('legacy_id', $legacyOrderId)->value('id');

        return $id === null ? null : $this->orderMap[$legacyOrderId] = (int) $id;
    }

    private function attendeeIdFor(int $legacyAttendeeId): ?int
    {
        if (isset($this->attendeeMap[$legacyAttendeeId])) {
            return $this->attendeeMap[$legacyAttendeeId];
        }

        $id = Attendee::withTrashed()->where('legacy_id', $legacyAttendeeId)->value('id');

        return $id === null ? null : $this->attendeeMap[$legacyAttendeeId] = (int) $id;
    }

    private function questionMapFor(int $legacyEventId, int $legacyQuestionId): ?int
    {
        return $this->questionMap[$legacyEventId.':'.$legacyQuestionId] ?? null;
    }

    /**
     * Legacy order_items only carry the ticket title, so resolve the ticket by
     * matching titles within the order's event.
     */
    private function ticketIdByTitle(int $legacyEventId, string $title): ?int
    {
        static $titleMap = [];

        if (! isset($titleMap[$legacyEventId])) {
            $titleMap[$legacyEventId] = Ticket::query()
                ->whereIn('legacy_id', $this->legacy()
                    ->table('tickets')
                    ->where('event_id', $legacyEventId)
                    ->pluck('id'))
                ->get(['id', 'title'])
                ->mapWithKeys(fn (Ticket $ticket) => [Str::lower(trim($ticket->title)) => (int) $ticket->getKey()])
                ->all();
        }

        return $titleMap[$legacyEventId][Str::lower(trim($title))] ?? null;
    }

    // -----------------------------------------------------------------
    // Value helpers
    // -----------------------------------------------------------------

    private function currencyCode(mixed $legacyCurrencyId, ?string $fallback = null): string
    {
        if ($this->currencyCodes === []) {
            $this->currencyCodes = $this->legacy()->table('currencies')->pluck('code', 'id')->all();
        }

        $code = $legacyCurrencyId !== null
            ? Str::upper(trim((string) ($this->currencyCodes[(int) $legacyCurrencyId] ?? '')))
            : '';

        if ($code !== '') {
            return $code;
        }

        return $fallback ?? (string) config('events.default_currency', 'GBP');
    }

    private function timezoneName(mixed $legacyTimezoneId): ?string
    {
        if ($legacyTimezoneId === null || $legacyTimezoneId === '') {
            return null;
        }

        if ($this->timezones === []) {
            $this->timezones = $this->legacy()->table('timezones')->pluck('name', 'id')->all();
        }

        $name = trim((string) ($this->timezones[(int) $legacyTimezoneId] ?? ''));

        return $this->validTimezone($name);
    }

    /**
     * `events.event_timezone` is sometimes an Olson name, sometimes a legacy
     * timezone id stored as a string, and often empty.
     */
    private function resolveEventTimezone(mixed $legacyTimezone, int $organiserId): string
    {
        $value = trim((string) ($legacyTimezone ?? ''));

        if ($value !== '') {
            if (is_numeric($value)) {
                $mapped = $this->timezoneName((int) $value);

                if ($mapped !== null) {
                    return $mapped;
                }
            } else {
                $valid = $this->validTimezone($value);

                if ($valid !== null) {
                    return $valid;
                }
            }
        }

        $organiserTimezone = Organiser::query()->whereKey($organiserId)->value('timezone');

        return $this->validTimezone((string) $organiserTimezone) ?? (string) config('events.default_timezone', 'Europe/London');
    }

    private function validTimezone(string $timezone): ?string
    {
        $timezone = trim($timezone);

        if ($timezone === '') {
            return null;
        }

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : null;
    }

    /**
     * Legacy event/ticket datetimes are local to the event timezone.
     */
    private function toUtc(mixed $value, string $timezone): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value, $timezone)->utc()->toDateTimeString();
        } catch (Throwable) {
            $this->warn("could not parse date [{$value}] in timezone [{$timezone}].");

            return null;
        }
    }

    /**
     * Legacy order timestamps are system generated and stored in UTC.
     */
    private function legacyTimestamp(mixed $date, mixed $timestamp): ?string
    {
        $value = $date ?: $timestamp;

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value, 'UTC')->utc()->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    private function coordinate(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function countryCode(mixed $code, mixed $name = null): ?string
    {
        $code = Str::upper(trim((string) ($code ?? '')));

        if (strlen($code) === 2 && ctype_alpha($code)) {
            return $code;
        }

        $name = trim((string) ($name ?? ''));

        if ($name === '') {
            return null;
        }

        $iso = $this->legacy()
            ->table('countries')
            ->where('name', $name)
            ->value('iso_3166_2');

        $iso = Str::upper(trim((string) $iso));

        return strlen($iso) === 2 ? $iso : null;
    }

    private function nullIfBlank(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  object  $row  Legacy order row.
     */
    private function paymentIntent(object $row): ?string
    {
        $intent = $this->nullIfBlank($row->payment_intent ?? null);

        if ($intent === null) {
            return null;
        }

        if (Order::query()->where('stripe_payment_intent_id', $intent)->exists()) {
            $this->warn("order {$row->id}: payment intent {$intent} already exists, it was not copied.");

            return null;
        }

        return $intent;
    }

    private function uniqueOrganiserSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organiser';
        $slug = $base;
        $i = 2;

        while (isset($this->usedEventSlugs['org:'.$slug]) || Organiser::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        $this->usedEventSlugs['org:'.$slug] = true;

        return $slug;
    }

    private function uniqueEventSlug(int $organiserId, mixed $legacySlug, string $title): string
    {
        $base = Str::slug((string) ($legacySlug ?? '')) ?: (Str::slug($title) ?: 'event');
        $slug = $base;
        $i = 2;

        while (isset($this->usedEventSlugs[$organiserId.':'.$slug])
            || Event::withTrashed()->where('organiser_id', $organiserId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        $this->usedEventSlugs[$organiserId.':'.$slug] = true;

        return $slug;
    }

    private function uniqueOrderReference(mixed $legacyReference): string
    {
        $reference = trim((string) ($legacyReference ?? ''));

        if ($reference !== ''
            && ! isset($this->usedOrderReferences[$reference])
            && ! Order::query()->where('reference', $reference)->exists()) {
            $this->usedOrderReferences[$reference] = true;

            return $reference;
        }

        if ($reference !== '') {
            $this->warn("order reference {$reference} was already taken, a new one was generated.");
        }

        do {
            $generated = ReferenceGenerator::order();
        } while (isset($this->usedOrderReferences[$generated]) || Order::query()->where('reference', $generated)->exists());

        $this->usedOrderReferences[$generated] = true;

        return $generated;
    }

    private function attendeeReference(mixed $legacyReference): string
    {
        $reference = trim((string) ($legacyReference ?? ''));

        if ($reference !== ''
            && ! isset($this->usedAttendeeReferences[$reference])
            && ! Attendee::withTrashed()->where('reference', $reference)->exists()) {
            $this->usedAttendeeReferences[$reference] = true;

            return $reference;
        }

        do {
            $generated = ReferenceGenerator::attendee();
        } while (isset($this->usedAttendeeReferences[$generated])
            || Attendee::withTrashed()->where('reference', $generated)->exists());

        $this->usedAttendeeReferences[$generated] = true;

        return $generated;
    }

    // -----------------------------------------------------------------
    // Files
    // -----------------------------------------------------------------

    /**
     * @param  object|null  $existing  Existing event when re-running the import.
     */
    private function eventCoverImage(?Event $existing, object $row): ?string
    {
        if ($existing !== null && $existing->cover_image_path !== null) {
            return $existing->cover_image_path;
        }

        $candidates = [
            $this->eventImages[(int) $row->id] ?? null,
            $row->bg_image_path ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($this->nullIfBlank($candidate) === null) {
                continue;
            }

            $stored = $this->copyLegacyFile($candidate, 'events');

            if ($stored !== null) {
                return $stored;
            }
        }

        return null;
    }

    private function copyLegacyFile(mixed $path, string $directory): ?string
    {
        $path = $this->nullIfBlank(str_replace('\\', '/', (string) ($path ?? '')));

        if ($path === null) {
            return null;
        }

        if (array_key_exists($path, $this->fileCache)) {
            return $this->fileCache[$path];
        }

        $source = $this->locateLegacyFile($path, $directory);

        if ($source === null) {
            $this->warn("missing legacy file: {$path}");

            return $this->fileCache[$path] = null;
        }

        $target = trim($directory.'/'.basename($source), '/');

        if ($this->dryRun) {
            return $this->fileCache[$path] = $target;
        }

        try {
            $handle = fopen($source, 'rb');
            Storage::disk('public')->writeStream($target, $handle);

            if (is_resource($handle)) {
                fclose($handle);
            }
        } catch (Throwable $e) {
            $this->warn("could not copy {$path}: ".$e->getMessage());

            return $this->fileCache[$path] = null;
        }

        return $this->fileCache[$path] = $target;
    }

    private function locateLegacyFile(string $path, string $directory): ?string
    {
        $uploads = rtrim((string) config('events.legacy_uploads'), '/');

        $candidates = array_filter([
            $path,
            base_path($path),
            public_path($path),
            $uploads.'/'.ltrim(Str::after($path, 'user_content/'), '/'),
            $uploads.'/'.$directory.'/'.basename($path),
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    // -----------------------------------------------------------------
    // Plumbing
    // -----------------------------------------------------------------

    private function legacy(): Connection
    {
        return DB::connection('legacy');
    }

    private function legacyHasColumn(string $table, string $column): bool
    {
        static $cache = [];

        $cache[$table] ??= Schema::connection('legacy')->getColumnListing($table);

        return in_array($column, $cache[$table], true);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function rows($query): \Illuminate\Support\Collection
    {
        if ($this->limit !== null) {
            $query->limit($this->limit);
        }

        return $query->get();
    }

    /**
     * @param  Closure(): Model  $factory
     */
    private function create(string $entity, Closure $factory): int
    {
        $this->counts[$entity]['created']++;

        if ($this->dryRun) {
            return -1 * (++$this->syntheticIds);
        }

        return (int) $factory()->getKey();
    }

    private function update(string $entity, Model $model, array $attributes): void
    {
        $this->counts[$entity]['updated']++;

        if ($this->dryRun) {
            return;
        }

        $model->fill($attributes)->save();
    }

    private function skip(string $entity, string $reason): void
    {
        $this->counts[$entity]['skipped']++;
        $this->warn($reason);
    }

    private function warn(string $message): void
    {
        if (! in_array($message, $this->warnings, true)) {
            $this->warnings[] = $message;
        }
    }

    private function warnUnmatchedClub(string $identifier): void
    {
        $this->warn("club [{$identifier}] has no matching organiser, its Stripe account was not imported.");
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }
}
