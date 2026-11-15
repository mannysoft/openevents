<form class="online_payment" action="<?php echo route('postCreateOrder', ['event_id' => $event->id]); ?>" method="post" id="stripe-payment-form">
    <div class="form-row">

        <div>
            <label>First Name</label>
            <input class="form-control" name="firstname" value="{{ session('firstname') }}" placeholder="Enter First Name" required>
        </div>

        <div>
            <label>Last Name</label>
            <input class="form-control" name="lastname" value="{{ session('lastname') }}" placeholder="Enter Last Name" required>
        </div>

        <div>
            <label>Email</label>
            <input type="email" class="form-control" name="email" value="{{ session('email') }}" placeholder="Enter Email" required>
        </div>

        <!-- Show only if the user dont have phone number in database -->
        <div>
            <label>Phone Number</label>
            <input class="form-control" name="phone" value="{{ session('phone') }}" required>
        </div>

        <div>
            @if($pointsEnough)

            <div style="margin-top: 15px" class="alert alert-info">

                <h4>Points balance: {{ $totalPoints }} points</h4>

                <input type="hidden" name="equivalet_points" value="{{ $equivaletPoints }}">
                <input type="checkbox" id="use_points_check" name="use_points_check" value="1">
                <input type="hidden" id="use_points" name="use_points" value="0">

                <label for="use_points_check">Redeem {{ $equivaletPoints }} points to pay for this event</label>

                <br>

                <strong>Note:</strong> If you use your points, you will not be able to get a refund for this event.

            </div>
        @endif
        </div>

        <label style="margin-top: 15px" for="card-element" id="card-label">
            @lang("Public_ViewEvent.stripe_credit_or_debit_card")
        </label>
        <div id="card-element">

        </div>

        <div id="card-errors" role="alert"></div>

        <div style="margin-top: 20px;">
            <input id="accept_terms" type="checkbox" required>
            <label for="accept_terms">I have read and accept the <a href="https://globalwomanclub.com/terms-conditions" target="_blank">Global Woman Event Terms & Conditions</a></label>

            <p>Please note Global Woman event tickets are non refundable. Email <a href="mailto:support@globalwomanclub.com">support@globalwomanclub.com</a> should you wish to reschedule.@if($clientSecret != null).@endif</p>
        </div>
    </div>
    @csrf
    <br>

    <input id="btn-paycard" class="btn btn-lg btn-success card-submit" style="width:100%;" type="submit" value="@lang('Public_ViewEvent.complete_payment')">

</form>

<form class="online_payment" action="<?php echo route('postCreateOrder', ['event_id' => $event->id]); ?>" method="post" id="points-payment-form">
    <input type="hidden" id="equivalet_points" name="equivalet_points" value="{{ $equivaletPoints }}">
    @csrf
</form>

<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@if($clientSecret != null)
@include('Public.ViewEvent.Partials.new_stripe')
@else
<script type="text/javascript">
    var stripe = Stripe('<?php echo $account_payment_gateway->config['publishableKey']; ?>');
    var elements = stripe.elements();

    var style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    var card = elements.create('card', {
        hidePostalCode: true,
        style: style
    });

    card.mount('#card-element');

    card.addEventListener('change', function(event) {
        var displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    document.getElementById("use_points_check").addEventListener("change", showHideButtons);

    function showHideButtons(e) {
        
        if ($('#use_points_check').is(':checked')) {
            $('#use_points').val('1');
            $('#btn-paycard').val('Pay with '+$('#equivalet_points').val()+' points');
            $('#card-label').hide();
            card.unmount();
        } else {
            
            $('#use_points').val('0');
            $('#btn-paycard').val('Complete Payment');
            $('#card-label').show();
            card.mount('#card-element');
        }
    }

    document.getElementById("stripe-payment-form").addEventListener("submit", payByPoints);

    function payByPoints(event) {
        
        event.preventDefault();

        if ($('#use_points_check').is(':checked')) {
           
            var paybyPointsForm = document.getElementById("points-payment-form");

            const extraFields = [
                { name: 'firstname', value: $('input[name="firstname"]').val() },
                { name: 'lastname', value: $('input[name="lastname"]').val() },
                { name: 'email', value: $('input[name="email"]').val() },
                { name: 'phone', value: $('input[name="phone"]').val() },
                { name: 'stripeToken', value: '' }, // No token needed for points payment
                { name: 'use_points', value: '1' }, // Use points
                { name: '_token', value: $('input[name="_token"]').val() }
            ];

            extraFields.forEach(field => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = field.name;
                input.value = field.value;
                paybyPointsForm.appendChild(input);
            });

            paybyPointsForm.submit();
        }

        return true;
    }
</script>
@endif
<style type="text/css">
    .StripeElement {
        box-sizing: border-box;

        /* height: 40px; */

        padding: 10px 12px;

        border: 1px solid #e0e0e0 !important;
        border-radius: 4px;
        background-color: white;

        box-shadow: 0 1px 3px 0 #e6ebf1;
        -webkit-transition: box-shadow 150ms ease;
        transition: box-shadow 150ms ease;
        margin-bottom: 20px;
    }

    .StripeElement--focus {
        box-shadow: 0 1px 3px 0 #cfd7df;
    }

    .StripeElement--invalid {
        border-color: #fa755a;
    }

    .StripeElement--webkit-autofill {
        background-color: #fefde5 !important;
    }
</style>