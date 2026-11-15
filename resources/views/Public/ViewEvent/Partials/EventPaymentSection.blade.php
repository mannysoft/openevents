@if(session('access_token') == null && config('services.lobby_sign_in.enabled'))
<section id="event-payment-section" class="container">
    <div class="row">
       <a class="btn btn-lg btn-success card-submit" href="{{ route('register.url') }}" target="OpenLobbyLogin">
            Login to get member price
        </a>
    </div>
</section>
@endif
<section id='order_form' class="container">
    @if (session('is_bring_friend') === true)
    <div class="row">
        <h1 class="section_head">
            Your friend information
        </h1>
        <div id="friend-section-error" class="alert alert-danger" style="display:none;">
            Please enter your friend's details before proceeding to payment.
        </div>
        <div class="col-md-8">
            @livewire('friend-details')
        </div>
    </div>

    <script type="text/javascript">
        window.friendDetailsSaved = {{ session('bring_friend_details') ? 'true' : 'false' }};

        document.addEventListener('livewire:initialized', function () {
            Livewire.on('friend-details-saved', function () {
                window.friendDetailsSaved = true;
                document.getElementById('friend-section-error').style.display = 'none';
            });

            Livewire.on('friend-details-removed', function () {
                window.friendDetailsSaved = false;
            });
        });

        // Block payment button if friend details are not yet saved.
        // Capture phase on document fires before btn-paycard's own onclick (new_stripe path)
        // and before the form submit (old Stripe path).
        document.addEventListener('click', function (e) {
            var payBtn = document.getElementById('btn-paycard');
            if (payBtn && (e.target === payBtn || payBtn.contains(e.target))) {
                if (!window.friendDetailsSaved) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var errorDiv = document.getElementById('friend-section-error');
                    errorDiv.style.display = 'block';
                    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }, true);
    </script>
    @endif
    <div class="row">
        <h1 class="section_head">
            @lang("Public_ViewEvent.payment_information")
        </h1>
    </div>
    @if($payment_failed)
    <div class="row">
        <div class="col-md-8 alert-danger" style="text-align: left; padding: 10px">
            @lang("Order.payment_failed")
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-md-12" style="text-align: center">
            @lang("Public_ViewEvent.below_order_details_header")
        </div>
        <div class="col-md-4 col-md-push-8">
            <div class="panel">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="ico-cart mr5"></i>
                        @lang("Public_ViewEvent.order_summary")
                    </h3>
                </div>

                <div class="panel-body pt0">
                    <table class="table mb0 table-condensed">
                        @foreach($tickets as $ticket)
                        <tr>
                            <td class="pl0">{{{$ticket['ticket']['title']}}} X <b>{{$ticket['qty']}}</b></td>
                            <td style="text-align: right;">
                                @isFree($ticket['full_price'])
                                @lang("Public_ViewEvent.free")
                                @else
                                {{ money($ticket['full_price'], $event->currency) }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        {{-- If we have speaking slot in the session, show it --}}
                        @if(session()->has('speaking_slot_details'))
                        <tr>
                            <td class="pl0">{{ session('speaking_slot_details')['name'] }} X <b>1</b></td>
                            <td style="text-align: right;">
                                {{ session('speaking_slot_details')['currency'] }}{{ session('speaking_slot_details')['price'] }}
                            </td>
                        </tr>
                        @endif
                        @isFree($ticket['full_price'])
                        <tr><td></td></tr>
                        @else
                            @if (session('is_bring_friend') !== true)
                            {{-- If this is true then hide the coupon code input field. This is because the bring a friend ticket is already discounted and we don't want to allow further discounts on it. --}}
                            <tr>
                                <td style="text-align: right;" colspan="2">
                                    <div>
                                        <input id="coupon_code" class="form-control" name="coupon_code" value="{{ session('couponCode') }}" placeholder="Enter Coupon Code">
                                    </div>
                                </td>
                            </tr>
                            @endif
                        @endif
                        <tr>
                            <td class="pl0" style="color: red;"><span id="discount_code">{{ session('couponCode') }}</span></td>
                            <td style="text-align: right;">
                                <span id="discount_amount" class="text-red" style="color: red;">@if(session('discountAmount'))-{{ money(session('discountAmount'), $event->currency) }}@endif</span>
                            </td>
                        </tr>
                    </table>
                </div>
                @if($order_total > 0)
                <div class="panel-footer">
                    <h5>
                        @lang("Public_ViewEvent.total"): <span id="discounted_amount" style="float: right;font-weight: bold;">{{ $orderService->getOrderTotalWithBookingFee(true) }}</span>
                    </h5>
                    @if($event->organiser->charge_tax)
                    <h5>
                        {{ $event->organiser->tax_name }} ({{ $event->organiser->tax_value }}%):
                        <span style="float: right;"><b>{{ $orderService->getTaxAmount(true) }}</b></span>
                    </h5>
                    <h5>
                        <strong>@lang("Public_ViewEvent.grand_total")</strong>
                        <span style="float: right;"><b>{{  $orderService->getGrandTotal(true) }}</b></span>
                    </h5>
                    @endif
                </div>
                @endif

            </div>
            <div class="help-block">
                {!! @trans("Public_ViewEvent.time", ["time"=>"<span id='countdown'></span>"]) !!}
            </div>
        </div>
        <div class="col-md-8 col-md-pull-4">
            <div class="row">

                @if($order_requires_payment)
                @include('Public.ViewEvent.Partials.OfflinePayments')
                @endif
                @if(View::exists($payment_gateway['checkout_blade_template']) && $order_requires_payment)
                @include($payment_gateway['checkout_blade_template'])
                @endif
                @if(!$order_requires_payment)
                @include('Public.ViewEvent.Partials.PaymentFree')
                @endif


            </div>
        </div>
    </div>
</section>
@if(session()->get('message'))
<script>showMessage('{{session()->get('message')}}');</script>
@endif

<script type="text/javascript">
    $(document).ready(function () {

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

          $('#coupon_code').keyup(debounce(function() {
            var self = $(this);

            $('#btn-paycard').attr('disabled', 'disabled');

            if (self.val() == '') {
                document.getElementById('discount_code').innerHTML = "";
                document.getElementById('discounted_amount').innerHTML = "";
                $('#btn-paycard').removeAttr('disabled');
                return;
            }

            $.ajax({
                url: "{{ route('check.coupon') }}?coupon=" + self.val() + '&event_id={{$event->id}}&event_price={{$event->event_member_non_member_price}}',
                type: 'GET',
                cache: false,
                processData: false,
                success: function(response) {
                    $('#percent_off').val(response.percent_off);
                    document.getElementById('discount_code').innerHTML = response.id;
                    document.getElementById('discounted_amount').innerHTML = "{{$event->currency->symbol_left}}" + response.discounted_amount;
                    document.getElementById('discount_amount').innerHTML = "-{{$event->currency->symbol_left}}" + response.discount_amount;

                    // Change some amount here
                    console.log('okay');
                    console.log(response);

                    $('#btn-paycard').removeAttr('disabled');
                    location.reload();
                },
                error: function(error) {
                    console.log(error);
                    // document.getElementById('discount_text').innerHTML = "";
                    // document.getElementById('discounted_amount').innerHTML = "Invalid Coupon";
                    // document.getElementById('discounted_amount').innerHTML = "{{$event->currency->symbol_left}}" + response.discounted_amount;
                    $('#btn-paycard').removeAttr('disabled');
                }
            });

            console.log(self.val());

        }, 1000));
    });

    let windowObjectReference = null; // global variable
    function openRequestedTab(url, windowName) {
        if (windowObjectReference === null || windowObjectReference.closed) {

            let width = 340;

            let height = 380;

            let left = (screen.width - width) / 2;
            let top = (screen.height - height) / 4;

            // const windowFeatures = "left=100,top=100,width=340,height=380";

            windowObjectReference = window.open(url, windowName, 'resizable=false, width=340, height=380' + ', top='
                + top + ', left=' + left);

            // const windowFeatures = "left=100,top=100,width=340,height=380";
            // windowObjectReference = window.open(url, windowName, windowFeatures);

            var pollTimer = window.setInterval(function() {
                if (windowObjectReference.closed !== false) { // !== is required for compatibility with Opera
                    window.clearInterval(pollTimer);
                    showTickets();
                }
            }, 200);
        } else {
            windowObjectReference.focus();
        }
    }
    
    function showTickets() {
        window.setTimeout(function() {
            if (windowObjectReference.closed) {
                // alert("Pop-up definitely closed");
                // windowObjectReference.reload();
                // We can put something here so that we can reload the page
                // or reload the content of the ticket and session
                location.reload();

                // Show tickets
                // document.getElementById("ticket-grid").style.display = 'block';

                // // Show sign in as
                // document.getElementById("sign-in-as").style.display = 'block';

                // // Hide login form
                // document.getElementById("before-login").style.display = 'none';

                // We can update pricing here
            }
        }, 200);
    }

    const link = document.querySelector("a[target='OpenLobbyLogin']");
    if (link != null) {
       link.addEventListener(
            "click",
            (event) => {
                openRequestedTab(link.href);
                event.preventDefault();
            },
            false,
        );
    }

</script>
