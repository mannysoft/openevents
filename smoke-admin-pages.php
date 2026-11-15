<?php

$_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.sqlite.database' => ':memory:']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

use App\Livewire\Admin\DiscountCodes\Index as Discounts;
use App\Livewire\Admin\Questions\Index as Questions;
use App\Models\Event;
use App\Models\Organiser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$failures = 0;

function check(string $label, bool $condition, string $extra = ''): void
{
    global $failures;

    if (! $condition) {
        $failures++;
    }

    echo ($condition ? 'PASS' : 'FAIL').' - '.$label.($extra !== '' ? ' :: '.$extra : '').PHP_EOL;
}

$user = User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => bcrypt('secret')]);
$organiser = Organiser::create([
    'user_id' => $user->id,
    'name' => 'Org',
    'slug' => 'org',
    'email' => 'org@example.com',
    'currency_code' => 'GBP',
    'timezone' => 'Europe/London',
]);
$event = Event::create([
    'organiser_id' => $organiser->id,
    'title' => 'Test Event',
    'slug' => 'test-event',
    'start_date' => now()->addMonth(),
    'end_date' => now()->addMonth()->addDay(),
    'timezone' => 'Europe/London',
    'currency_code' => 'GBP',
    'status' => 'draft',
    'is_public' => true,
]);

Auth::login($user);

/* ---------------------------------------------------------------- discounts */

$discounts = Livewire\Livewire::test(Discounts::class, ['event' => $event]);

$discounts->assertOk();
$discounts->assertSee('No discount codes yet');
$discounts->assertSee('Test Event');

$discounts->call('openCreate')
    ->set('code', 'summer10')
    ->set('type', 'percentage')
    ->set('value', '10')
    ->set('min_quantity', '1')
    ->call('save')
    ->assertHasNoErrors();

$percentage = $event->discountCodes()->first();
check('percentage code created and uppercased', $percentage?->code === 'SUMMER10', (string) $percentage?->code);
check('percentage value stored as 10', (int) $percentage?->value === 10, (string) $percentage?->value);
check('percentage type cast', $percentage?->type === App\Enums\DiscountType::Percentage);
check('unlimited quantity is null', $percentage?->quantity_available === null);
check('active by default', $percentage?->is_active === true);

$discounts->assertSee('SUMMER10')->assertSee('10%')->assertSee('0 / ∞');

$discounts->call('openCreate')
    ->set('code', 'fixed5')
    ->set('type', 'fixed')
    ->set('value', '5.50')
    ->set('quantity_available', '25')
    ->set('min_quantity', '2')
    ->set('expires_at', '2030-01-01T12:30')
    ->set('is_active', false)
    ->call('save')
    ->assertHasNoErrors();

$fixed = $event->discountCodes()->where('code', 'FIXED5')->first();
check('fixed value converted to minor units', (int) $fixed?->value === 550, (string) $fixed?->value);
check('quantity_available stored', (int) $fixed?->quantity_available === 25);
check('min_quantity stored', (int) $fixed?->min_quantity === 2);
check('expiry stored', $fixed?->expires_at?->format('Y-m-d H:i') === '2030-01-01 12:30', (string) $fixed?->expires_at?->format('Y-m-d H:i'));
check('inactive stored', $fixed?->is_active === false);

$discounts->assertSee('£5.50')->assertSee('Inactive');

// validation
$discounts->call('openCreate')
    ->set('code', 'SUMMER10')
    ->set('value', '10')
    ->call('save')
    ->assertHasErrors(['code']);

$discounts->set('code', 'BIG')->set('type', 'percentage')->set('value', '150')->call('save')->assertHasErrors(['value']);
$discounts->set('value', '0')->call('save')->assertHasErrors(['value']);
$discounts->set('type', 'fixed')->set('value', '0.001')->call('save')->assertHasErrors(['value']);
$discounts->set('type', 'percentage')->set('value', '10')->set('min_quantity', '0')->call('save')->assertHasErrors(['min_quantity']);

// unique-per-event ignores the row being edited
$discounts->call('openEdit', $percentage->id)
    ->set('code', 'SUMMER10')
    ->set('value', '15')
    ->call('save')
    ->assertHasNoErrors();

check('edit updated value in place', (int) $percentage->fresh()->value === 15, (string) $percentage->fresh()->value);
check('edit did not create a second code', $event->discountCodes()->count() === 2, (string) $event->discountCodes()->count());

// quantity must not drop below quantity_used
$percentage->update(['quantity_used' => 5]);
$discounts->call('openEdit', $percentage->id)
    ->set('quantity_available', '3')
    ->call('save')
    ->assertHasErrors(['quantity_available']);

$discounts->call('openEdit', $percentage->id)
    ->set('quantity_available', '10')
    ->call('save')
    ->assertHasNoErrors();

$discounts->call('toggleActive', $fixed->id);
check('toggle activated', $fixed->fresh()->is_active === true);
$discounts->call('toggleActive', $fixed->id);
check('toggle deactivated', $fixed->fresh()->is_active === false);

$discounts->assertSeeHtml('wire:confirm');

$discounts->call('delete', $fixed->id);
check('deleted discount code', $event->discountCodes()->count() === 1);
$discounts->assertHasNoErrors();

/* ---------------------------------------------------------------- questions */

$questions = Livewire\Livewire::test(Questions::class, ['event' => $event]);

$questions->assertOk();
$questions->assertSee('No questions yet');

$questions->call('openCreate')
    ->set('title', 'Dietary requirements')
    ->set('type', 'text')
    ->set('is_required', true)
    ->set('applies_to', 'attendee')
    ->call('save')
    ->assertHasNoErrors();

$text = $event->questions()->where('title', 'Dietary requirements')->first();
check('text question created', $text !== null);
check('required flag stored', $text?->is_required === true);
check('options null for text', $text?->options === null);
check('first question sort order', (int) $text?->sort_order === 1, (string) $text?->sort_order);

$questions->call('openCreate')
    ->set('title', 'Shirt size')
    ->set('type', 'select')
    ->set('options', 'Small, Medium , Large, Medium')
    ->set('applies_to', 'order')
    ->call('save')
    ->assertHasNoErrors();

$select = $event->questions()->where('title', 'Shirt size')->first();
check('options stored as array', $select?->options === ['Small', 'Medium', 'Large'], json_encode($select?->options));
check('applies_to stored', $select?->applies_to === 'order');
check('second question sort order appended', (int) $select?->sort_order === 2, (string) $select?->sort_order);

// select/checkbox require at least one option
$questions->call('openCreate')
    ->set('title', 'Bad question')
    ->set('type', 'checkbox')
    ->set('options', ' , ')
    ->call('save')
    ->assertHasErrors(['options']);
check('invalid question not persisted', $event->questions()->where('title', 'Bad question')->doesntExist());

$questions->set('title', '')->call('save')->assertHasErrors(['title']);

$questions->call('openEdit', $select->id)
    ->set('options', 'One, Two')
    ->call('save')
    ->assertHasNoErrors();
check('edit replaced options', $select->fresh()->options === ['One', 'Two'], json_encode($select->fresh()->options));

// reordering
$questions->call('move', $select->id, 'up');
check('moved up: select is first', $event->questions()->orderBy('sort_order')->first()->id === $select->id);
$questions->call('move', $select->id, 'up');
check('moving past the top is a no-op', $event->questions()->orderBy('sort_order')->first()->id === $select->id);

$questions->call('move', $select->id, 'down');
check('moved down: text is first', $event->questions()->orderBy('sort_order')->first()->id === $text->id);
$questions->call('move', $text->id, 'down');
check('moving past the bottom is a no-op', $event->questions()->orderBy('sort_order')->first()->id === $text->id);

// answers count
$order = App\Models\Order::create([
    'reference' => 'ORD-1',
    'event_id' => $event->id,
    'organiser_id' => $organiser->id,
    'email' => 'guest@example.com',
    'currency_code' => 'GBP',
]);
$attendee = App\Models\Attendee::create([
    'event_id' => $event->id,
    'order_id' => $order->id,
    'email' => 'guest@example.com',
    'reference' => 'ATT-1',
]);
App\Models\Answer::create(['attendee_id' => $attendee->id, 'question_id' => $text->id, 'answer_text' => 'Vegan']);
App\Models\Answer::create(['attendee_id' => $attendee->id, 'question_id' => $text->id, 'answer_text' => 'Halal']);

$questions->assertSeeHtml('wire:confirm');
$rendered = $questions->html();
check('answers count rendered', str_contains($rendered, '>2<'), 'answers_count');

$questions->call('delete', $text->id);
check('question deleted', $event->questions()->whereKey($text->id)->doesntExist());
check('answers cascade deleted', App\Models\Answer::where('question_id', $text->id)->doesntExist());

/* --------------------------------------------------------------- authorise */

$stranger = User::create(['name' => 'Stranger', 'email' => 'stranger@example.com', 'password' => bcrypt('secret')]);
Auth::login($stranger);

try {
    Livewire\Livewire::test(Discounts::class, ['event' => $event]);
    check('stranger cannot view discounts', false);
} catch (Illuminate\Auth\Access\AuthorizationException) {
    check('stranger cannot view discounts', true);
}

try {
    Livewire\Livewire::test(Questions::class, ['event' => $event]);
    check('stranger cannot view questions', false);
} catch (Illuminate\Auth\Access\AuthorizationException) {
    check('stranger cannot view questions', true);
}

echo PHP_EOL.($failures === 0 ? 'ALL CHECKS PASSED' : $failures.' CHECK(S) FAILED').PHP_EOL;
exit($failures === 0 ? 0 : 1);
