<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * While the Lobby is off, members cannot sign in: every "Login Now" button leads to the Lobby,
 * which answers 502. They do not need to. A member's price and her members-only access come from
 * the Lounge, by email (is_member() -> fetch_user_status()), and the booking form registers her
 * without the Lobby since #350. So while LOBBY_SIGN_IN is unset the pages hide every way into
 * the Lobby and tell members to book with their membership email.
 *
 * These read the templates' source: the pages need the Lounge and the events database to render.
 */
class MemberBookingWithoutLobbyTest extends TestCase
{
    use BootsWithoutDatabase;

    private const SWITCH = "config('services.lobby_sign_in.enabled')";

    public function test_lobby_sign_in_is_off_unless_set(): void
    {
        $this->assertFalse(config('services.lobby_sign_in.enabled'));
    }

    public function test_the_booking_box_hides_member_sign_in_and_tells_members_what_to_do(): void
    {
        $view = $this->view('Public/ClubEvents/Partials/TicketBookSection.blade.php');

        $open = strpos($view, '@if('.self::SWITCH.')'."\n".'                            <div class="memberLogin">');
        $this->assertNotFalse($open, 'the member sign-in box is not behind the switch');

        // The box holds its own @if/@endif, so find the @endif that closes the switch: it sits
        // after the "OR" divider, right before the booking form.
        $close = strpos($view, "</div>\n                            @endif\n                            <div class=\"nonMemberLogin\">", $open);
        $this->assertNotFalse($close, 'the switch does not close right before the booking form');
        $box = substr($view, $open, $close - $open);
        $this->assertStringContainsString('Login Now', $box);
        $this->assertStringContainsString("route('register.url')", $box);
        $this->assertStringContainsString('<div class="divider">', $box);

        $this->assertStringContainsString('<h4 class="m-0">Book your place</h4>', $view);
        $this->assertStringContainsString('Members: use the email address of your membership, and member prices apply.', $view);
    }

    public function test_the_event_is_live_link_does_not_send_visitors_to_the_lobby(): void
    {
        $view = $this->view('Public/ClubEvents/Partials/TicketBookSection.blade.php');

        $lobby = strpos($view, "<a href='https://lobby.globalwomanclub.com/oauth?app_id=");
        $this->assertNotFalse($lobby);

        // The line that writes the Lobby link sits directly under the switch.
        $before = substr($view, 0, $lobby);
        $this->assertMatchesRegularExpression('/@if\('.preg_quote(self::SWITCH, '/').'\)\s*\n[^\n]*$/', $before);
    }

    public function test_the_payment_page_hides_the_member_price_sign_in_button(): void
    {
        $view = $this->view('Public/ViewEvent/Partials/EventPaymentSection.blade.php');

        $this->assertStringStartsWith("@if(session('access_token') == null && ".self::SWITCH.')', $view);
    }

    private function view(string $path): string
    {
        return str_replace("\r\n", "\n", file_get_contents(resource_path('views/'.$path)));
    }
}
