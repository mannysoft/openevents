{{-- Post-payment congratulations & lounge app section --}}
<div class="row">
    <div class="col-md-8 col-md-offset-2 text-center" style="margin-top: 30px; margin-bottom: 30px; padding: 30px; background: #f9f9f9; border-radius: 8px;">
        {{-- <h2 style="font-size: 24px; font-weight: 600; letter-spacing: .05em; margin-bottom: 15px;">
            🎉 Congratulations for securing your ticket!
        </h2> --}}
        <p style="font-size: 16px; color: #555; margin-bottom: 10px;">
            Please check your email to find your <a class="ticket_download_link"
                href="{{ route('showOrderTickets', ['order_reference' => $order->order_reference] ).'?download=1' }}" target="_blank">
                {{ @trans("Public_ViewEvent.tickets") }}</a> and all details — an agenda will follow soon.
        </p>
        <hr style="margin: 20px auto; width: 60%; border-color: #ddd;">
        <p style="font-size: 16px; color: #555; margin-bottom: 5px;">
            Meanwhile, explore our <strong>Lounge App</strong> and enjoy some <strong>free zone access</strong>.
        </p>
        <p style="font-size: 16px; color: #555; margin-bottom: 20px;">
            By downloading the app you will get a better idea about us.
        </p>
        <p style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 20px;">
            See you on the other side! 👋
        </p>

        {{-- QR Code for the Lounge App --}}
        @if(config('app.lounge_app_qr_code'))
        <div style="margin-top: 15px;">
            <p style="font-size: 14px; color: #888; margin-bottom: 10px; text-transform: uppercase; letter-spacing: .1em;">Scan to download the app</p>
            <img src="{{ config('app.lounge_app_qr_code') }}"
                    alt="Scan to download the Global Woman Lounge App"
                    style="width: 180px; height: 180px; border: 4px solid #eee; border-radius: 8px; padding: 8px; background: #fff;">
        </div>
        @endif

        <div class="row">
                <div class="col-xs-6 text-center">
                    <a href="https://globalwomanlounge.com/link" target="_blank">
                        <img src="https://globalwomanlobby.com/assets/images/apple.png" alt="Download on the App Store" style="max-width: 270px; height: auto;">
                    </a>
                </div>
                <div class="col-xs-6 text-center">
                    <a href="https://globalwomanlounge.com/link" target="_blank">
                        <img src="https://globalwomanlobby.com/assets/images/android.png" alt="Get it on Google Play" style="max-width: 270px; height: auto;">
                    </a>
                </div>
            </div>

        <div style="margin-top: 20px;">
            <a href="{{ 'https://app.globalwomanlounge.com' }}"
                target="_blank"
                style="display: inline-block; background-color: #dc273f; color: #fff; padding: 12px 30px; border-radius: 4px; text-decoration: none; font-size: 15px; font-weight: 600;">
                Visit the Lounge
            </a>
        </div>
    </div>
</div>