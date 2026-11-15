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
    Route::livewire('/', Dashboard::class)->name('admin.dashboard');

    Route::livewire('/organisers', OrganisersIndex::class)->name('admin.organisers.index');
    Route::livewire('/organisers/create', OrganiserForm::class)->name('admin.organisers.create');
    Route::livewire('/organisers/{organiser}/edit', OrganiserForm::class)->name('admin.organisers.edit');
    Route::livewire('/organisers/{organiser}/stripe', StripeConnect::class)->name('admin.organisers.stripe');

    Route::livewire('/organisers/{organiser}/events', EventsIndex::class)->name('admin.events.index');
    Route::livewire('/organisers/{organiser}/events/create', EventForm::class)->name('admin.events.create');
    Route::livewire('/organisers/{organiser}/events/{event}/edit', EventForm::class)->name('admin.events.edit');

    Route::livewire('/events/{event}/tickets', TicketsIndex::class)->name('admin.tickets.index');
    Route::livewire('/events/{event}/orders', OrdersIndex::class)->name('admin.orders.index');
    Route::livewire('/events/{event}/attendees', AttendeesIndex::class)->name('admin.attendees.index');
    Route::get('/events/{event}/attendees/export/{format}', [AttendeeExportController::class, '__invoke'])->name('admin.attendees.export');
    Route::livewire('/events/{event}/check-in', Scanner::class)->name('admin.checkin.index');
    Route::livewire('/events/{event}/discounts', DiscountCodesIndex::class)->name('admin.discounts.index');
    Route::livewire('/events/{event}/questions', QuestionsIndex::class)->name('admin.questions.index');
    Route::livewire('/events/{event}/messages', MessagingIndex::class)->name('admin.messages.index');

    Route::livewire('/orders/{order}', OrderShow::class)->name('admin.orders.show');
    Route::livewire('/payouts', PayoutsIndex::class)->name('admin.payouts.index');
});
