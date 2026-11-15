<script>
    // New Stripe Integration
    @if($stripeAccountId != null)
    const stripe = Stripe("{{ config('services.stripe.key') }}", {
        stripeAccount: '{{ $stripeAccountId }}' // Connected account ID
    });
    @else
    const stripe = Stripe("{{ config('services.stripe.key') }}");
    @endif

    initialize();

    // Create a Checkout Session
    async function initialize() {

        const clientSecret = "{{ $clientSecret }}";
        const elements = stripe.elements({clientSecret});

        const paymentElement = elements.create('payment');
        paymentElement.mount('#card-element');

        setuserdetails();

        document.getElementById('btn-paycard').onclick = async (event) => {
            // The order form around this button posts to postCreateOrder. Never send it from
            // here: the card is paid below, and the order is made on Stripe's return.
            event.preventDefault();

            // preventDefault() also skips the browser's own check of the form, so ask for it:
            // the terms box and the name, email and phone fields are required.
            if (!document.getElementById('stripe-payment-form').reportValidity()) {
                return;
            }

            if (!$('#accept_terms').is(':checked')) {
                return false;
            }

            $('#btn-paycard').attr('disabled', 'disabled');
            $('#btn-paycard').val('Processing...');
            const {error} = await stripe.confirmPayment({
                elements,
                confirmParams: {
                return_url: '{{ route("showEventCheckoutPaymentReturn", [
                    'event_id' => $event->id,
                    'is_payment_successful' => 1]) }}',
                },
            });
            if (error) {
                // Show error to your customer
                $('#btn-paycard').removeAttr('disabled');
                $('#btn-paycard').val('Complete Payment');
                alert(error.message);
            }
        };
    }

    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this,
                args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    $('#accept_terms').click(function() {
        setuserdetails();
    });

    function setuserdetails() {
        $.ajax({
            url: "{{ route('setUserSession') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                firstname: $('input[name="firstname"]').val(),
                lastname: $('input[name="lastname"]').val(),
                email: $('input[name="email"]').val(),
                phone: $('input[name="phone"]').val(),
                club: '{{ $event->club }}'
            },
            success: function(response) {
                console.log('Data changed');
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    $('input[name="firstname"], input[name="lastname"], input[name="email"]').keyup(debounce(function() {
        console.log('changed');
        var self = $(this);

        setuserdetails();

        console.log(self.val());

    }, 500));
</script>