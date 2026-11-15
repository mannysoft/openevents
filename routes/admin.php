<?php

use App\Http\Controllers\AttendeeExportController;
use App\Livewire\Admin\Attendees\Index as AttendeesIndex;
use App\Livewire\Admin\CheckIn\Scanner;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DiscountCodes\Index as DiscountCodesIndex;
use App\Livewire\Admin\Events\Form as EventForm;
use App\Livewire\Admin\Events\Index as EventsIndex;
use App\Livewire\Admin\Messaging\Index as MessagingIndex;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Livewire\Admin\Organisers\Form as OrganiserForm;
use App\Livewire\Admin\Organisers\Index as OrganisersIndex;
use App\Livewire\Admin\Organisers\StripeConnect;
use App\Livewire\Admin\Payouts\Index as PayoutsIndex;
use App\Livewire\Admin\Questions\Index as QuestionsIndex;
use App\Livewire\Admin\Tickets\Index as TicketsIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('dashboard')->group(function () {
    Route::get('/', Dashboard::class)->name('admin.dashboard');

    Route::get('/organisers', OrganisersIndex::class)->name('admin.organisers.index');
    Route::get('/organisers/create', OrganiserForm::class)->name('admin.organisers.create');
    Route::get('/organisers/{organiser}/edit', OrganiserForm::class)->name('admin.organisers.edit');
    Route::get('/organisers/{organiser}/stripe', StripeConnect::class)->name('admin.organisers.stripe');

    Route::get('/organisers/{organiser}/events', EventsIndex::class)->name('admin.events.index');
    Route::get('/organisers/{organiser}/events/create', EventForm::class)->name('admin.events.create');
    Route::get('/organisers/{organiser}/events/{event}/edit', EventForm::class)->name('admin.events.edit');

    Route::get('/events/{event}/tickets', TicketsIndex::class)->name('admin.tickets.index');
    Route::get('/events/{event}/orders', OrdersIndex::class)->name('admin.orders.index');
    Route::get('/events/{event}/attendees', AttendeesIndex::class)->name('admin.attendees.index');
    Route::get('/events/{event}/attendees/export/{format}', AttendeeExportController::class)->name('admin.attendees.export');
    Route::get('/events/{event}/check-in', Scanner::class)->name('admin.checkin.index');
    Route::get('/events/{event}/discounts', DiscountCodesIndex::class)->name('admin.discounts.index');
    Route::get('/events/{event}/questions', QuestionsIndex::class)->name('admin.questions.index');
    Route::get('/events/{event}/messages', MessagingIndex::class)->name('admin.messages.index');

    Route::get('/orders/{order}', OrderShow::class)->name('admin.orders.show');
    Route::get('/payouts', PayoutsIndex::class)->name('admin.payouts.index');
});
