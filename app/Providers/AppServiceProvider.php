<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Builder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Builder::defaultStringLength(191);
        // No ?debug=1 switch here: it let any visitor turn on debug mode (full error pages) and the
        // Debugbar on any URL. Use APP_DEBUG / DEBUGBAR_ENABLED in a non-production environment.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('event_throttle', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    Log::warning('Too many requests.');
                    return response('Too many requests', 429);
                });
            // return Limit::perSecond(10)->by($request->user()?->id ?: $request->ip());
        });

        // GET resend-email has no authentication (the Lounge calls it server to server). It mails
        // an order's confirmation, and its 200/404 tells whether an email booked an event. Per IP
        // against probing; per email+event against mailing one buyer over and over.
        RateLimiter::for('resend-email', function (Request $request) {
            $email = $request->query('email');
            $eventId = $request->query('event_id');
            $pair = strtolower(trim(is_string($email) ? $email : '')) . '|' . (is_string($eventId) ? trim($eventId) : '');
            $tooMany = fn () => response('Too many requests', 429);

            return [
                Limit::perMinute(10)->by('ip-minute:' . $request->ip())->response($tooMany),
                Limit::perHour(100)->by('ip-hour:' . $request->ip())->response($tooMany),
                Limit::perHour(3)->by('email-event:' . sha1($pair))->response($tooMany),
            ];
        });
    }
}
