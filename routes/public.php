<?php

use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TicketPdfController;
use App\Livewire\Public\Checkout;
use App\Livewire\Public\EventIndex;
use App\Livewire\Public\EventPage;
use App\Livewire\Public\OrderConfirmation;
use App\Livewire\Public\OrganiserPage;
use App\Livewire\Public\Ticket;
use Illuminate\Support\Facades\Route;

Route::livewire('/events', EventIndex::class)->name('events.index');
Route::livewire('/o/{organiser}', OrganiserPage::class)->name('organisers.show');
Route::livewire('/e/{event}', EventPage::class)->name('events.show');
Route::livewire('/e/{event}/checkout', Checkout::class)->name('checkout');
Route::livewire('/orders/{order}', OrderConfirmation::class)->name('orders.confirmation');
Route::livewire('/tickets/{attendee}', Ticket::class)->name('tickets.show');
Route::get('/tickets/{attendee}/pdf', [TicketPdfController::class, 'show'])->name('tickets.pdf');

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');
Route::get('/stripe/connect/{organiser}/return', [StripeConnectController::class, 'return'])->name('stripe.connect.return');
Route::get('/stripe/connect/{organiser}/refresh', [StripeConnectController::class, 'refresh'])->name('stripe.connect.refresh');
