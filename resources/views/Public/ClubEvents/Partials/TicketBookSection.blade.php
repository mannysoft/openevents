<!-- Public.ClubEvents.Partials.TicketBookSection -->
<section id="section-ticket-book" class="section-ticket-book">
    @php
    $joinUrl = '';
    $joinPurchasedAmount = '';
    $eventSpeakerData = Illuminate\Support\Facades\DB::table('Wo_Clickfunnel_Event_SpeakerData')
        ->where('event_id', $event->id)
        ->where('email_id', $email)
        ->get();
    if(empty($eventSpeakerData->first())){
        $speaker = 0;
    } else {
        $speaker = 1;
    }
    if($email!=""){
        $eventPurchaseData = Illuminate\Support\Facades\DB::table('Wo_Clickfunnel_Event_Purchase')
            ->where('event_id', $event->id)
            ->where('email_id', $email)
            ->get();
        if(empty($eventPurchaseData->first())){
            $purchased = 0;
        } else {
            foreach($eventPurchaseData as $eventJoinData){
                $joinUrl = $eventJoinData->zoom_url;
                $joinPurchasedAmount = $eventJoinData->amount;
            }
            $purchased = 1;
        }
    } else {
        $purchased = 0;
    }

    $currency = "$";
    if($event->currency_id == "2")
    $currency = "€";
    elseif($event->currency_id == "3")
    $currency = "£";
    elseif($event->currency_id == "27")
    $currency = "₺";
    // $loginData = 1; // For testing TODO
    @endphp

    <div class="section-title" id="sign-in-as" @if($loginData=="1") @else style="display:none;" @endif>
        <div class="inline-div">
            <div class="flex-div">
                <div class="user-image" style="background-image:url('{{$avatar}}')"></div>
                <div>
                    <h1>Welcome {{$firstname}}</h1>
                    <p><a href="{{  route('logout') }}" class="text-red">Sign in as Different User</a></p>
                    @if($purchased=="1" && $event->event_ttype=='Online' && $oldEvent=="0")
                    <p><a href="{{$joinUrl}}" class="text-red">Get Your Zoom Link</a></p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            @if($canBookRight)
            <div class="@if($event->event_ttype=='Hybrid' || $event->event_ttype=='hybrid' || @$event->vip_price != null) col-lg-6 @else col-lg-8 @endif col-sm-12 align-self-start">
            @else
            <div class="@if($event->event_ttype=='Hybrid' || $event->event_ttype=='hybrid' || @$event->vip_price != null) col-lg-6 @else col-lg-6 @endif col-sm-12 align-self-start">
            @endif
                @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
                <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="event_video" data-has-video="true" data-video-only="true" data-club-id="{{ $clubData->id }}" data-content="{{ @$clubData->event_video }}" data-label="Event Video URL (You can paste YouTube video here)">
                    Edit Video
                </a>
                @endif
                @if ($eventsVideo != '')
                <div class="embed-responsive embed-responsive-16by9">
                    @if($event->id=="1" || $event->id=="2" || $event->id=="3")
                    <video id="video-wrapper" class="embed-responsive-item">
                        <source src="https://www.youtube.com/watch?v=5GbqrNidpzE">
                    </video>
                    @else
                    <!-- Club video from clubs table in events db -->
                    {{-- allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" --}}
                    <iframe width="560" height="315" src="{{$eventsVideo}}" title="Global Woman Club video player" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    @endif
                </div>
                @endif

                <div class="video-content">
                    @if($club=='Losangeles')
                    <h1 class="m-0">Los Angeles {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='Orange-County')
                    <h1 class="m-0">Orange County, California {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='Newyork')
                    <h1 class="m-0">New York {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='Nottinghill')
                    <h1 class="m-0">Notting Hill {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='Metrodc')
                    <h1 class="m-0">Metro DC {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='London-mayfair')
                    <p class="m-0" style="font-family:'Vidaloka';font-size:21px;">{{$event->title}}</p> <br>
                    @else
                    @if($club == 'Accra')
                    <h1 class="m-0">Global Woman Club Online {{ $event->start_date->format('Y') }}</h1>
                    @else
                        @if (! is_string($event->start_date))
                        <h1 class="m-0">{{ $videoTitle ?? $club}} {{ $event->start_date->format('Y') }}</h1>
                        @endif
                    @endif
                    @endif

                    @if($oldEvent=='0')
                    <ul id="mini-countdown"></ul>
                    @endif

                    <div>
                        @if($purchased=="1" && $event->event_ttype=='Online' && $oldEvent=="0")
                        <a href="{{$joinUrl}}" class="btn bg-red text-white" style="background-color: green !important;">Get Your Zoom Link</a>
                        @endif
                    </div>
                </div>
            </div>

            @if($canBookRight)
            <div class="@if($event->event_ttype=='Hybrid' || $event->event_ttype=='hybrid' || @$event->vip_price != null) col-lg-6 @else col-lg-4 @endif col-sm-12 align-self-start">
            @else
            <div class="@if($event->event_ttype=='Hybrid' || $event->event_ttype=='hybrid' || @$event->vip_price != null) col-lg-6 @else col-lg-6 @endif col-sm-12 align-self-start">
            @endif
                <div class="ticket-book-content">
                    @if(session('referal_email') && session('referal_email_accepted') === null)
                    <!-- Invitation Banner -->
                    <div id="invite-banner" class="card mb-3" style="border: 2px solid #da2926; border-radius: 12px; background-color: #fff5f5;">
                        <div class="card-body text-center" style="padding: 20px;">
                            <div style="margin-bottom: 10px;">
                                <i class="fas fa-envelope-open-text" style="font-size: 28px; color: #da2926;"></i>
                            </div>
                            <h5 style="color: #da2926; font-weight: bold; margin-bottom: 5px;">You are invited by {{ session('referal_first_name') }} {{ session('referal_last_name') }}</h5>
                            <p style="color: #555; margin-bottom: 15px;">We'd love to have you join us.</p>
                            <div>
                                <button type="button" class="btn bg-red text-white" style="border-radius: 20px; padding: 8px 40px; font-weight: bold;" onclick="respondToInvite('accept')">Accept Invite</button>
                                <button type="button" class="btn btn-outline-dark" style="border-radius: 20px; padding: 8px 30px; margin-left: 5px;" onclick="respondToInvite('decline')">Decline</button>
                            </div>
                        </div>
                    </div>
                    <script>
                        function respondToInvite(action) {
                            $.ajax({
                                url: '{{ route("referral.respond") }}',
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    action: action
                                },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        if (action === 'accept') {
                                            $('#invite-banner').html('<div class="card-body text-center" style="padding: 15px;"><i class="fas fa-check-circle" style="font-size: 24px; color: #28a745;"></i><p style="color: #28a745; font-weight: bold; margin: 5px 0 0;">Invite accepted! Please choose your ticket below.</p></div>');
                                            setTimeout(function() { $('#invite-banner').fadeOut(); }, 3000);
                                        } else {
                                            $('#invite-banner').fadeOut();
                                        }
                                    }
                                }
                            });
                        }
                    </script>
                    @endif

                    <!-- before login -->
                    <!-- If already login then dont show div  -->
                    @if($canBookRight)
                    <div id="before-login" class="before-login" style="display:none;">
                    @else
                    <div id="before-login" class="before-login" @if($loginData=="1" ) style="display:none;" @endif>
                    @endif
                        @if($oldEvent=="0")
                        <div class="uniqueBox">
                            @if(config('services.lobby_sign_in.enabled'))
                            <div class="memberLogin">
                                <h4 class="m-0">Member Login</h4>

                                <div style="height: 250px;">
                                    <img height="100" width="200" src="{{ config('app.url') }}/assets/images/logo-dark.png" />
                                      <p style="color: #555; font-size: 14px; margin-bottom: 15px; line-height: 1.5;">
                                    Log in with your member account to unlock <strong style="color: #da2926;">exclusive member pricing</strong> on all tickets.
                                </p>
                                </div>

                                {{-- <a href="https://lobby.globalwomanclub.com/oauth?app_id={{ config('services.lobby_app.app_id') }}" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">Login Now</a> --}}
                                @if( ! $canBookRight)
                                {{-- Show only if cannot book right, because if can book right, means already login, then no need to show login button --}}
                                <a href="{{ route('register.url') }}" target="OpenLobbyLogin" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">
                                    Login Now
                                </a>
                                @endif

                            </div>
                            <div class="divider">
                                OR
                            </div>
                            @endif
                            <div class="nonMemberLogin">
                                @if(config('services.lobby_sign_in.enabled'))
                                <h4 class="m-0">Non-Member Login</h4>
                                @else
                                <h4 class="m-0">Book your place</h4>
                                <p style="color: #555; font-size: 14px; margin: 10px 0;">Members: use the email address of your membership, and member prices apply.</p>
                                @endif
                                <form class="form" action="" method="post">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-user"></i></div>
                                                <input type="text" class="form-control" id="firstname" placeholder="Enter First Name" required>
                                                <input type="hidden" class="form-control" id="club" value="{{$club}}">
                                                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                                                <input type="hidden" class="form-control" id="eventId" value="{{$event->id}}">
                                                <input type="hidden" class="form-control" id="lobbyeventId" value="{{$event->lobby_event_id}}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-user"></i></div>
                                                <input type="text" class="form-control" id="lastname" placeholder="Enter Last Name" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-envelope"></i></div>
                                                <input type="email" class="form-control" id="email" onfocus="myFunction1()" placeholder="Enter Email Address" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group telephone">
                                                <input type="tel" class="form-control" name="telephone[main]" id="telephone" onfocus="myFunction1()" placeholder="e.g. +1 702 123 4567" required>
                                            </div>
                                        </div>
                                    </div>
                                    <p id='message' class="m-0 text-center"></p>
                                    <button type="button" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white" id="submitrEGISTER" onclick="return formRegisterSubmit();" value="Book My Spot">Book My Spot</button>
                                </form>
                            </div>
                        </div>
                        @else
                        @php
                        $eventImage = App\Models\EventImage::where('event_id', '=', $event->id)->get();

                        @endphp

                        @foreach($eventImage as $img)
                        <img src="https://davxegdvkjbks.cloudfront.net/{{$img->image_path}}" alt="" style="max-width: 100%;">
                        @endforeach
                        <button type="button" class="btn bg-red text-white" style="background: green !important" id="submitrEGISTER" value="Booking Closed">Booking Closed</button>
                        @endif
                    </div>

                    <!-- ticket grid -->
                    @if($canBookRight)
                    <div id="ticket-grid" class="ticket-grid">
                    @else
                    <div id="ticket-grid" class="ticket-grid" @if($loginData=="1") @else style="display:none;" @endif>
                    @endif
                       
                        @if($event->event_ttype=='Online' || $event->event_ttype=='online')
                            @include('Public.ClubEvents.Partials.ticket-button')
                        @elseif($event->event_ttype=='In-Person' || $event->event_ttype=='in-person')
                            @if(@$event->vip_price != null)
                                @include('Public.ClubEvents.Partials.ticket-button-vip')
                            @elseif($isBringFriend === true)
                                @include('Public.ClubEvents.Partials.ticket-button-bring-friend')
                            @else
                                @include('Public.ClubEvents.Partials.ticket-button')
                            @endif
                        @else
                            @include('Public.ClubEvents.Partials.ticket-button-hybrid')
                            @if(@$event->vip_price != null)
                                <br>
                                @include('Public.ClubEvents.Partials.ticket-button-vip', ['hybridVip' => true])
                            @endif
                        @endif
                    </div>

                        <!-- apply member -->
                    <div class="apply-member" @if($loginData=="1") @else style="display:none;" @endif>
                        @if($speaker == 0)
                        <div class="row justify-content-center" id="apply-to-speak">
                            @if($purchased==1)
                            <p class="text-center">Would you like to speak at our events?</p>
                            <div class="col-md-7">
                                <a href="javascript:void(0);" onclick="applyToSpeak();" class="btn bg-red text-white">Apply to speak</a>
                            </div>
                            @endif
                        </div>
                        @else
                        <p class="m-0 text-center">Your request has been sent to your Regional Director<br> we will get in touch with you shortly</p>
                        @endif
                    </div>

                            <!-- speaker form -->
                            <div class="speaker-form" style="display:none;">
                                <div class="welcome">
                                    <h2>Speaking Fill Form</h2>
                                </div>
                                <form class="formSpeaker" id="addspeakerform" action="#" method="post">
                                    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-user"></i></div>
                                                <input type="text" class="form-control" id="firstname" name="firstname" placeholder="Enter First Name" value="{{$firstname}}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-user"></i></div>
                                                <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Enter Last Name" value="{{$lastname}}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-envelope"></i></div>
                                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter Email Address" value="{{$email}}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group telephone">
                                                <input type="tel" class="form-control" name="contact[main]" id="contact" onkeyup="myFunctionNumber()" placeholder="e.g. +1 702 123 4567" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-lightbulb"></i></div>
                                                <input type="text" class="form-control" id="topic" name="topic" onfocus="myFunction3()" placeholder="Enter Topic" required>
                                                <input type="hidden" class="form-control" id="event_id" name="event_id" value="{{$event->id}}">
                                                <input type="hidden" class="form-control" id="clubs" name="clubs" value="{{$club}}">
                                                <input type="hidden" class="form-control" id="speakNumber" name="speakNumber" value="">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="icon" style="height:90px;"><i class="far fa-file-alt"></i></div>
                                                <textarea type="text" class="form-control" id="shortDiscription" name="shortDiscription" onfocus="myFunction3()" placeholder="Enter Short Description" required></textarea>
                                            </div>
                                        </div>
                                        <p id='messageCodeSpeaker' class="text-center" style="font-size: 14px;margin: 0 0 1rem;"></p>
                                    </div>

                                    <div style="display:flex;">
                                        <a href="javascript:void(0);" onclick="cancelSpeak();" class="btn cancel">Cancel</a>
                                        <button type="submit" class="btn bg-red text-white" value="Submit">Submit</button>
                                    </div>
                                </form>
                            </div>




                            <!-- user form -->
                            <div class="user-form" style="display:none;">
                                <div class="welcome">
                                    <h2>Enter your details</h2>
                                </div>
                                <form class="formUser" id="adduserform" action="" method="post">
                                    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-user"></i></div>
                                                <input type="text" class="form-control" id="firstnameuser" name="firstnameuser" placeholder="Enter First Name" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-user"></i></div>
                                                <input type="text" class="form-control" id="lastnameuser" name="lastnameuser" placeholder="Enter Last Name" required>
                                                <input type="hidden" class="form-control" id="club" value="{{$club}}">
                                                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                                                <input type="hidden" class="form-control" id="eventId" value="{{$event->id}}">
                                                <input type="hidden" class="form-control" id="lobbyeventId" value="{{$event->lobby_event_id}}">
                                                <input type="hidden" class="form-control" id="price_data" name="price_data" value="{{@$event_price}}">

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="icon"><i class="far fa-envelope"></i></div>
                                                <input type="email" class="form-control" id="emailuser" name="emailuser" placeholder="Enter Email Address" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group telephone">
                                                <div class="icon"><i class="far fa-file-alt"></i></div>
                                                <input type="tel" class="form-control" name="contactuser" id="contactuser" placeholder="Enter Mob (+1 702 123 4567)" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="icon" style="height:90px;"><i class="far fa-file-alt"></i></div>
                                                <textarea type="text" class="form-control" id="addressuser" name="addressuser" placeholder="Enter Address" required></textarea>
                                            </div>
                                        </div>

                                        <p id='messageCodeUser' class="text-center" style="font-size: 14px;margin: 0 0 1rem;"></p>
                                    </div>
                                    <!-- <p id='message' class="m-0 text-center"></p> -->
                                    <div style="display:flex;">
                                        <a href="javascript:void(0);" onclick="cancelUser();" class="btn cancel">Cancel</a>

                                        <button type="button" class="btn bg-red text-white" id="submitrEGISTER" onclick="return formUserSubmit();" value="Submit">Submit</button>
                                    </div>
                                </form>
                            </div>




                        </div>
                    </div>
                </div>
            </div>
</section>

<!-- Modal -->
<div class="modal fade" id="mobileBackdrop" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="mobileBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">
            <form id="postBuyMobileForm" action="#" method="post">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="mobileBackdropLabel">Update your mobile number</h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Please note: Your mobile/cell number is the quickest and best way to reach you should there be any changes to the event you have booked, (e.g. time or date) important information to pass on to you, or to send a reminder</p>
                    <div class="row">
                        <div class="col-md-8">

                            <div class="form-group telephone">
                                <input type="tel" class="form-control" name="telephoneupdate[main]" id="telephoneupdate" onkeyup="myFunctionTelNumber()" onfocus="myFunction4()" placeholder="e.g. +1 702 123 4567" required>
                                <input type="hidden" class="form-control" id="telNumber" name="telNumber" value="">
                                <input type="hidden" class="form-control" id="lobbyIdUser" name="lobbyIdUser" value="{{$email}}">
                            </div>

                        </div>
                    </div>
                    <p id='messageCodeMobileno' class="text-center" style="font-size: 14px;margin: 0 0 1rem;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>

    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="couponCodeBackdrop" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="couponCodeBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">


            <form id="postBuyCouponCodeForm" action="#" method="post">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="couponCodeBackdropLabel">Guest User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <input type="hidden" class="form-control" id="event_id" name="event_id" value="{{$event->id}}">
                                <input type="hidden" class="form-control" id="lobbyIdUser" name="lobbyIdUser" value="{{$email}}">
                                <input type="text" class="form-control" name="couponCodeName" id="couponCodeName" placeholder="Enter name" required>
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control" name="couponCodeEmail" id="couponCodeEmail" placeholder="Enter email" required>
                            </div>
                            <div class="form-group telephone">
                                <input type="number" class="form-control" name="couponCodeTel" id="couponCodeTel" placeholder="Enter mobile number" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" style="background-color:#da2926;border-color:#da2926;">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="uniqueLinkGuestModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="uniqueLinkGuestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="uniqueLinkGuestForm" action="#" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="uniqueLinkGuestModalLabel">Enter your details</h5>
                </div>
                <div class="modal-body">
                    <p>Please enter your details to access this event.</p>
                    <div class="form-group">
                        <input type="text" class="form-control" id="uniqueLinkFullName" name="fullname" placeholder="Full name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" class="form-control" id="uniqueLinkEmail" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" class="form-control" id="uniqueLinkPhone" name="mobileNo" placeholder="Phone number" required>
                    </div>
                    <p id="uniqueLinkGuestMessage" class="m-0" style="font-size: 14px;"></p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" style="background-color:#da2926;border-color:#da2926;">Continue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="editContentModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="couponCodeBackdropLabel" aria-hidden="true">
    <!-- modal-lg for large -->
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="postEditContentForm" action="#" method="post" enctype="multipart/form-data">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="editContentLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <!-- <input type="number" class="form-control" name="couponCodeTel" id="couponCodeTel" placeholder="Enter mobile number" required> -->
                                <br>
                                <!-- <input type="file" multiple  class="form-control"> -->
                                <input type="file" name="file_upload" id="file_upload" class="form-control">
                                <input placeholder="Image URL" type="text" id="image_url" name="image_url" class="form-control">
                                <br>
                                <input placeholder="Video URL" type="text" id="video_url" name="video_url" class="form-control">
                            </div>

                            <div class="form-group">
                                <input type="hidden" class="form-control" id="event_id" name="event_id" value="{{$event->id}}">
                                <input type="hidden" class="form-control" id="content_field" name="content_field">
                                <input type="hidden" class="form-control" id="club_id" name="club_id">
                                <!-- <textarea id="edit-content" rows="20" class="form-control"></textarea> -->
                                <!-- <textarea id="edit-content" rows="20"></textarea> -->
                                <input id="x" value="" type="hidden" name="content">
                                <trix-editor input="x"></trix-editor>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background-color:#da2926;border-color:#da2926;">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editGalleryModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="couponCodeBackdropLabel" aria-hidden="true">
    <!-- modal-lg for large -->
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="postEditGalleryForm" action="{{ route('update.club.data') }}" method="post" enctype="multipart/form-data">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="editContentLabel">Upload images to gallery</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <div id="galler-images">

                                    <input type="hidden" class="form-control" id="event_id" name="event_id" value="{{$event->id}}">
                                    <input type="hidden" class="form-control" id="content_field" name="content_field">
                                    <input type="hidden" class="form-control" id="club_id" name="club_id">
                                    <input type="hidden" class="form-control" id="multiple_images_gallery" name="multiple_images_gallery" value="1">
                                    <input type="hidden" class="form-control" id="target_section" name="target_section">
                                    <br>

                                    <span>You can upload multiple files:</span>

                                    <input type="file" multiple class="form-control" name="gallery_files[]">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background-color:#da2926;border-color:#da2926;">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editDirectorImageModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="couponCodeBackdropLabel" aria-hidden="true">
    <!-- modal-lg for large -->
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="postEditDirectorImageForm" action="#" method="post">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDirectorImageLabel">Edit Director Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <br>

                                <div id="director-image">

                                    <input type="hidden" class="form-control" id="event_id" name="event_id" value="{{$event->id}}">
                                    <input type="hidden" class="form-control" id="content_field" name="content_field">
                                    <input type="hidden" class="form-control" id="club_id" name="club_id">

                                    <!-- <input type="file" multiple class="form-control" name="files[]"> -->

                                    <br>

                                    <input placeholder="Director Image URL" type="text" class="form-control" id="director_image" name="director_image">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background-color:#da2926;border-color:#da2926;">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteImageModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="couponCodeBackdropLabel" aria-hidden="true">
    <!-- modal-lg for large -->
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="deleteImageForm" action="#" method="post">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="editContentLabel">Delete Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <input type="hidden" id="delete_image_id" name="delete_image_id">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background-color:#da2926;border-color:#da2926;">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="login-lobby" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="couponCodeBackdropLabel" aria-hidden="true">
    <!-- modal-lg for large -->
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <form id="login-lobby-form" action="#" method="post">
                <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="editContentLabel">Login</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <input type="hidden" id="delete_image_id" name="delete_image_id">
                            </div>
                        </div>
                    </div>

                    <!-- <iframe src="https://lobby.globalwomanclub.com/index.php?link1=login-event" width="300" height="380" frameborder="0" allowtransparency="true"></iframe> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background-color:#da2926;border-color:#da2926;">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Booking Error Modal -->
<div class="modal fade" id="bookingErrorModal" tabindex="-1" aria-labelledby="bookingErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #da2926 0%, #b91c1a 100%); border: none; padding: 20px 24px;">
                <h5 class="modal-title" id="bookingErrorModalLabel" style="color: #fff; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 22px;"></i>
                    <span id="bookingErrorModalTitle">Unable to complete booking</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.9; text-shadow: none; font-weight: 400;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 28px 24px; text-align: center;">
                <p id="bookingErrorModalMessage" style="margin: 0; font-size: 15px; line-height: 1.6; color: #333;"></p>
                <p id="bookingErrorModalSupport" class="text-muted" style="margin: 16px 0 0; font-size: 13px; display: none;">
                    Need help? Contact
                    <a href="mailto:support@globalwomanclub.com" style="color: #da2926; font-weight: 600;">support@globalwomanclub.com</a>
                </p>
            </div>
            <div class="modal-footer" style="border: none; padding: 0 24px 24px; justify-content: center;">
                <button type="button" class="btn text-white" data-dismiss="modal" style="background-color: #da2926; border-color: #da2926; padding: 8px 32px; border-radius: 24px; font-weight: 500;">
                    Got it
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://lobby.globalwomanclub.com/themes/wondertag/javascript/moment/moment.js"></script>
<script src="https://lobby.globalwomanclub.com/themes/wondertag/javascript/moment/moment-timezone-with-data.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="{{ config('app.url') }}/assets/javascript/intlTelInput.min.js"></script>
<script src="{{ config('app.url') }}/assets/javascript/intlTelInput-jquery.min.js"></script>
<script>
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

    /**
     * Show a professional booking error modal instead of a native alert().
     * Automatically shows the support-contact line when the message mentions
     * support or upgrading (e.g. the Mentors Club in-person block).
     */
    function showBookingErrorModal(message, title) {
        var $modal = $('#bookingErrorModal');
        if (!$modal.length) {
            alert(message);
            return;
        }

        $('#bookingErrorModalMessage').text(message || 'Something went wrong. Please try again.');
        $('#bookingErrorModalTitle').text(title || 'Unable to complete booking');

        var lower = (message || '').toLowerCase();
        var showSupport = lower.indexOf('support@globalwomanclub.com') !== -1
            || lower.indexOf('upgrade') !== -1
            || lower.indexOf('mentors club') !== -1;

        $('#bookingErrorModalSupport').css('display', showSupport ? 'block' : 'none');

        $modal.modal('show');
    }

    //on click play video
    function videoPlay() {
        $('#video-wrapper').trigger('play');
        $('#paly').hide();
        $('#pause').show();
        $("#pause").css("opacity", "0");
    }

    function videoPause() {
        $('#video-wrapper').trigger('pause');
        $('#paly').show();
        $('#pause').hide();
    }

    var phone_number = window.intlTelInput(document.querySelector("#telephone"), {
        autoHideDialCode: false,
        separateDialCode: true,
        hiddenInput: "full",
        utilsScript: "//cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.3/js/utils.js"
    });
    var telephone_update = window.intlTelInput(document.querySelector("#telephoneupdate"), {
        autoHideDialCode: false,
        separateDialCode: true,
        hiddenInput: "full",
        utilsScript: "//cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.3/js/utils.js"
    });
    var contact_number = window.intlTelInput(document.querySelector("#contact"), {
        autoHideDialCode: false,
        separateDialCode: true,
        hiddenInput: "full",
        utilsScript: "//cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.3/js/utils.js"
    });

    function myFunction1() {
        $('#message').html(' ');
    }

    function myFunction3() {
        $('#messageCodeSpeaker').html(' ');
    }

    function myFunction4() {
        $('#messageCodeMobileno').html(' ');
    }

    function myFunctionNumber() {
        var full_number12 = contact_number.getNumber(intlTelInputUtils.numberFormat.E164);
        console.log(full_number12, 'full_number12');
        document.getElementById("speakNumber").value = full_number12;
    }

    function myFunctionTelNumber() {
        var full_number121 = telephone_update.getNumber(intlTelInputUtils.numberFormat.E164);

        document.getElementById("telNumber").value = full_number121;
    }
    var start_date = "<?php echo $event->start_date ?>";
    var timzone = "<?php echo $event->event_timezone ?>";

    // Set the date we're counting down to
    var countDownDate = moment.tz(start_date, timzone);
    countDownDate.format(); // 2014-06-01T17:00:00+01:00

    var x = setInterval(function() {

        // Get today's date and time
        var now = new Date();
        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Output the result in an element with id="mini-countdown"
        document.getElementById("mini-countdown").innerHTML =
            "<li>" + "<span>" + days + "</span>" + "<p>" + "Days" + "</p>" + "</li>" +
            "<li>" + "<span>" + hours + "</span>" + "<p>" + "Hours" + "</p>" + "</li>" +
            "<li>" + "<span>" + minutes + "</span>" + "<p>" + "Minutes" + "</p>" + "</li>" +
            "<li>" + "<span>" + seconds + "</span>" + "<p>" + "Seconds" + "</p>" + "</li>"

        // If the count down is over, write some text
        if (distance < 0) {
            clearInterval(x);
            if (<?php echo $purchased ?> == 1) {
                if (<?php echo $oldEvent ?> == "0") {
                    document.getElementById("mini-countdown").innerHTML = "<a href='<?php echo $joinUrl; ?>' class='btn bg-red text-white'>Join Now</a>";
                }
            } else {
                if (<?php echo $loginData ?> == "1") {
                    if (<?php echo $oldEvent ?> == "0") {
                        document.getElementById("mini-countdown").innerHTML = "<p style='color:red;font-size:20px;font-weight:500;'>Please book the ticket to join the event</p>";
                    }
                } else {
                    if (<?php echo $oldEvent ?> == "0") {
@if(config('services.lobby_sign_in.enabled'))
                        document.getElementById("mini-countdown").innerHTML = "<a href='https://lobby.globalwomanclub.com/oauth?app_id=dd94cac1a7f8f47192b6' class='btn text-white' style='background-color:green;'>Event is Live Now</a>";
@else
                        document.getElementById("mini-countdown").innerHTML = "<p style='color:red;font-size:20px;font-weight:500;'>Please book the ticket to join the event</p>";
@endif
                    }
                }

            }
        }
    }, 1000);


    function applyToSpeak() {
        $("#apply-to-speak").hide();
        $(".ticket-grid").hide();
        $(".apply-member").hide();
        $(".speaker-form").show();
    }

    function cancelSpeak() {
        $("#apply-to-speak").show();
        $(".ticket-grid").show();
        $(".apply-member").show();
        $(".speaker-form").hide();
    }

    function applyUser() {
        $("#apply-to-speak").hide();
        $(".ticket-grid").hide();
        $(".apply-member").hide();
        $(".user-form").show();
    }

    function cancelUser() {
        $("#apply-to-speak").show();
        $(".ticket-grid").show();
        $(".apply-member").show();
        $(".user-form").hide();
    }

    var phone_number_lobby = '<?php echo $phone_number; ?>';

    function formRegisterSubmit() {

        $('#submitrEGISTER').text('Processing...');
        $('#submitrEGISTER').attr('disabled', true);

        var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,63})$/;
        var reg2 = /^\s*(?:\+?(\d{1,3}))?([-. (]*(\d{3})[-. )]*)?((\d{3})[-. ]*(\d{2,4})(?:[-.x ]*(\d+))?)\s*$/gm;
        var firstname = document.getElementById("firstname").value;
        var lastname = document.getElementById("lastname").value;
        var email = document.getElementById("email").value;
        var telephone = document.getElementById("telephone").value;
        var tokenDta = document.getElementById("token").value;
        var club = document.getElementById("club").value;
        var lobbyeventId = document.getElementById("lobbyeventId").value;
        var eventId = document.getElementById("eventId").value;
        var full_number = phone_number.getNumber(intlTelInputUtils.numberFormat.E164);

        if (firstname == "" || lastname == "" || email == "" || full_number == "") {
            $('#message').html('Please fill in all fields').css('color', 'red');
            $('#submitrEGISTER').text('Book my spot');
            $('#submitrEGISTER').attr('disabled', false);
            return false;
        }

        firstname = firstname.replace('.', '');

        if (reg2.test(telephone) == false) {
            $('#message').html('Invalid Contact Number').css('color', 'red');
            $('#submitrEGISTER').text('Book my spot');
            $('#submitrEGISTER').attr('disabled', false);
            return false;
        }
        if (reg.test(email) == false) {
            $('#message').html('Invalid Email Address').css('color', 'red');
            $('#submitrEGISTER').text('Book my spot');
            $('#submitrEGISTER').attr('disabled', false);
            return false;
        }

        var settings = {
            "url": "{{ config('app.url') }}/saveUserData",
            "method": "POST",
            "timeout": 0,
            "headers": {
                "Content-Type": "application/json"
            },
            "data": JSON.stringify({
                "email": email,
                "firstName": firstname,
                "lastName": lastname,
                "mobileNo": full_number,
                "eventId": eventId,
                "lobbyeventId": lobbyeventId,
                "club": club,
                "_token": tokenDta
            }),
        };

        $.ajax(settings).done(function(response) {
            var refId = response.referenceId;
            var status = response.status;

            if (status == "success") {
                location.reload();
            } else {
                $('#message').html('Please Try Again').css('color', 'red');
                $('#submitrEGISTER').text('Book my spot');
                $('#submitrEGISTER').attr('disabled', false);
            }
        });
        return true;
    }

    //   Speaker Data
    $(document).ready(function() {
        $('#addspeakerform').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '/saveSpeakerData',
                type: 'POST',
                data: $('#addspeakerform').serialize(),
                dataType: 'json',
                success: function(response) {
                    var status = response.status;
                    if (status == "success") {
                        location.reload();
                    } else if (status == "invalid") {
                        $('#messageCodeSpeaker').html('Please fill all the field').css('color', 'red');
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        });
    });


    function formUserSubmit() {
        var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,63})$/;
        var reg2 = /^\s*(?:\+?(\d{1,3}))?([-. (]*(\d{3})[-. )]*)?((\d{3})[-. ]*(\d{2,4})(?:[-.x ]*(\d+))?)\s*$/gm;
        var firstname = document.getElementById("firstnameuser").value;
        var lastname = document.getElementById("lastnameuser").value;
        var email = document.getElementById("emailuser").value;
        var addressuser = document.getElementById("addressuser").value;
        var contactuser = document.getElementById("contactuser").value;
        var tokenDta = document.getElementById("token").value;
        var club = document.getElementById("club").value;
        var lobbyeventId = document.getElementById("lobbyeventId").value;
        var eventId = document.getElementById("eventId").value;
        var price_data = document.getElementById("price_data").value;


        if (firstname == "" || lastname == "" || email == "" || contactuser == "" || addressuser == "") {
            $('#messageCodeUser').html('Please fill in all fields').css('color', 'red');
            return false;
        }


        if (reg2.test(contactuser) == false) {
            $('#messageCodeUser').html('Invalid Contact Number').css('color', 'red');
            return false;
        }
        if (reg.test(email) == false) {
            $('#messageCodeUser').html('Invalid Email Address').css('color', 'red');
            return false;
        }

        var settings = {
            "url": "{{ config('app.url') }}/saveUserDataLondon",
            "method": "POST",
            "timeout": 0,
            "headers": {
                "Content-Type": "application/json"
            },
            "data": JSON.stringify({
                "email": email,
                "firstName": firstname,
                "lastName": lastname,
                "mobileNo": contactuser,
                "eventId": eventId,
                "lobbyeventId": lobbyeventId,
                "club": club,
                "_token": tokenDta,
                "addressuser": addressuser
            }),
        };


        $.ajax(settings).done(function(response) {
            var refId = response.referenceId;
            var status = response.status;
            if (status == "success") {
                var form = new FormData();
                form.append("event_id", eventId);
                form.append("clubs", club);
                form.append("price_data", price_data);
                form.append("_token", tokenDta);

                var settings = {
                    "url": "/postBuyNow",
                    "method": "POST",
                    "timeout": 0,
                    "processData": false,
                    "mimeType": "multipart/form-data",
                    "contentType": false,
                    "data": form
                };

                $.ajax(settings).done(function(response) {
                    var obj = JSON.parse(response);
                    var status = obj.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        window.location.href = "{{ config('app.url') }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
                    } else {
                        // console.log(error);
                        // $('#messageCodeUser').html('Please Try Again').css('color', 'red');
                    }
                });
            } else {
                $('#messageCodeUser').html('Please Try Again').css('color', 'red');
            }
        });
        return true;
    }



    //   Post Buy Now
    $(document).ready(function() {
        $('#postBuyForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '/postBuyNow',
                type: 'POST',
                data: $('#postBuyForm').serialize(),
                dataType: 'json',
                success: function(response) {
                    var status = response.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        window.location.href = "{{ config('app.url') }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
                    }

                },
                error: function(error) {
                    console.log(error);
                }
            });
        });
    });



    //   Post Buy Now
    $(document).ready(function() {
        $('#postBuyForm11').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '/postBuyNow',
                type: 'POST',
                data: $('#postBuyForm11').serialize(),
                dataType: 'json',
                success: function(response) {
                    var status = response.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        window.location.href = "{{ request()->root() }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
                    } else {
                        showBookingErrorModal(response.message);
                        console.log(response);
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        });
    });



    //   Post Buy postBuyMobileFormNow
    $(document).ready(function() {
        $('#postBuyMobileForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '/postBuyMobileUpdate',
                type: 'POST',
                data: $('#postBuyMobileForm').serialize(),
                dataType: 'json',
                success: function(response) {
                    var status = response.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        window.location.href = "{{ config('app.url') }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
                    } else {
                        $('#messageCodeMobileno').html('Please Try after some time').css('color', 'red');
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        });
    });



    //   Post Buy postBuyMobileFormNow
    $(document).ready(function() {
        $('#postBuyCouponCodeForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '/postBuyCouponCodeForm',
                type: 'POST',
                data: $('#postBuyCouponCodeForm').serialize(),
                dataType: 'json',
                success: function(response) {
                    var status = response.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        document.getElementById('londonCouponCodeSuccessMsg').style.display = "block";
                        document.getElementById('londonCouponCodeSuccessMsg').innerHTML = "Guest Updated successfully!";

                        $('#couponCodeBackdrop').modal('hide');
                    } else {
                        $('#messageCodeMobileno').html('Please Try after some time').css('color', 'red');
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        });
    });

    // tinymce.init({
    //     selector: "textarea",
    //     width: '100%',
    //     height: 270,
    //     plugins: "link",
    //     statusbar: false,
    //     toolbar: "link"
    // });

    // Prevent bootstrap dialog from blocking focusin
    document.addEventListener('focusin', function(e) {
        if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
            e.stopImmediatePropagation();
        }
    });


    $(document).ready(function() {
        @if(session('has_unique_link_access'))
        $('#uniqueLinkGuestModal').modal('show');
        @endif

        @if($is_allowed_attend_event == false)
            $('#book_now, .book').on('click', function(event) {
                showBookingErrorModal("You've already attended an event with us and can't book another In-Person ticket on your current plan. Please upgrade to Entrepreneur or Influencer and contact support@globalwomanclub.com for more information.", 'Unable to complete booking');
                return false;
            });
            // showBookingErrorModal("You've already attended an event with us and can't book another In-Person ticket on your current plan. Please upgrade to Entrepreneur or Influencer and contact support@globalwomanclub.com for more information.", 'Unable to complete booking');
            // Disable any book button also if needed
            // $('#book_now').prop('disabled', true);
            // $('.book').prop('disabled', true);
            // $('#book_now').hide();
            // $('.book').hide();

            // $('#book-ticket-here').hide();
        @endif

        $('#uniqueLinkGuestForm').on('submit', function(e) {
            e.preventDefault();

            var fullname = $('#uniqueLinkFullName').val().trim();
            var email = $('#uniqueLinkEmail').val().trim();
            var mobileNo = $('#uniqueLinkPhone').val().trim();
            var eventTypeRaw = "{{ strtolower((string)($event->event_ttype ?? '')) }}";
            var eventType = eventTypeRaw.includes('in-person') ? 'in-person' : 'online';
            var namePattern = /^[A-Za-z][A-Za-z\s'.-]{1,99}$/;
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
            var phonePattern = /^\+?[0-9\s().-]{7,20}$/;

            if (!fullname || !email || !mobileNo) {
                $('#uniqueLinkGuestMessage').text('Please fill in all fields.').css('color', 'red');
                return;
            }

            if (!namePattern.test(fullname)) {
                $('#uniqueLinkGuestMessage').text('Please enter a valid full name.').css('color', 'red');
                return;
            }

            if (!emailPattern.test(email)) {
                $('#uniqueLinkGuestMessage').text('Please enter a valid email address.').css('color', 'red');
                return;
            }

            if (!phonePattern.test(mobileNo)) {
                $('#uniqueLinkGuestMessage').text('Please enter a valid phone number.').css('color', 'red');
                return;
            }

            @if(session('has_unique_link_access'))

            $.ajax({
                url: '/api/saveFreeUser',
                type: 'POST',
                dataType: 'json',
                data: {
                    eventId: "{{ $event->id }}",
                    emailId: email,
                    email: email,
                    fullname: fullname,
                    mobileNo: mobileNo,
                    club: "{{ $club ?? '' }}",
                    eventtType: eventType,
                    unique_token: "{{ request('inlnk') }}",
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response && response.status === 'error') {
                        var apiMessage = response.message || 'Unable to save details.';
                        if (response.errors) {
                            var firstField = Object.keys(response.errors)[0];
                            if (firstField && response.errors[firstField] && response.errors[firstField].length > 0) {
                                apiMessage = response.errors[firstField][0];
                            }
                        }

                        $('#uniqueLinkGuestMessage').text(apiMessage).css('color', 'red');
                        return;
                    }

                    $('#uniqueLinkGuestMessage').text('Your details were submitted successfully.').css('color', 'green');
                    $('#uniqueLinkGuestForm')[0].reset();
                    setTimeout(function() {
                        $('#uniqueLinkGuestModal').modal('hide');
                    }, 1500);
                },
                error: function(xhr) {
                    var errorMessage = 'Unable to save details. Please try again.';

                    if (xhr && xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            var errorField = Object.keys(xhr.responseJSON.errors)[0];
                            if (errorField && xhr.responseJSON.errors[errorField] && xhr.responseJSON.errors[errorField].length > 0) {
                                errorMessage = xhr.responseJSON.errors[errorField][0];
                            }
                        } else if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                    }

                    $('#uniqueLinkGuestMessage').text(errorMessage).css('color', 'red');
                }
            });

            @endif
        });

        $('#postEditContentForm').on('submit', function(e) {
            e.preventDefault();

            // var myContent = tinymce.activeEditor.getContent();

            var myContent = $('#x').val();

            var data = new FormData();

            data.append("event_id", $('#event_id').val());
            data.append("content_field", $('#content_field').val());
            data.append("club_id", $('#club_id').val());
            data.append("content", myContent);
            data.append("_token", "{{ csrf_token() }}");
            data.append("image_url", $('#image_url').val());
            data.append("video_url", $('#video_url').val());

            data.append('file_upload', $('#file_upload').get(0).files[0]); // Here's the important bit

            // var data = {
            //     "content_field": $('#content_field').val(),
            //     "club_id": $('#club_id').val(),
            //     "content": myContent,
            //     "_token": "{{ csrf_token() }}",
            //     "image_url": $('#image_url').val(),
            //     "video_url": $('#video_url').val(),
            // };

            $.ajax({
                url: "{{ route('update.club.data') }}",
                type: 'POST',
                data: data,
                // data: $('#postEditContentForm').serialize(),
                dataType: 'json',
                mimeType: 'multipart/form-data', // this too
                contentType: false,
                cache: false,
                processData: false,
                success: function(response) {
                    // var status = response.status;
                    // if(status == "success"){
                    // 	var eventIdDataId = "<?php echo $event->id ?>";
                    // 	document.getElementById('londonCouponCodeSuccessMsg').style.display = "block";
                    // 	document.getElementById('londonCouponCodeSuccessMsg').innerHTML = "Guest Updated successfully!";

                    // 	$('#editContentModal').modal('hide');
                    // } else{
                    // 	$('#messageCodeMobileno').html('Please Try after some time').css('color', 'red');
                    // }
                },
                error: function(error) {
                    console.log(error);
                    $('#editContentModal').modal('hide');
                    location.reload();
                }
            });
        });

        $('#editGalleryModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget) // Button that triggered the modal

            var targetSection = button.data('targetSection')

            $('#target_section').val(targetSection); // gallery or club-gallery
        });

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

            $('#book_now').attr('disabled', 'disabled');

            if (self.val() == '') {
                document.getElementById('discount_text').innerHTML = "";
                document.getElementById('discounted_amount').innerHTML = "";
                $('#book_now').removeAttr('disabled');
                return;
            }

            $.ajax({
                url: "{{ route('check.coupon') }}?coupon=" + self.val() + '&event_id={{$event->id}}&event_price=' + $('#event_price').val(),
                type: 'GET',
                cache: false,
                processData: false,
                success: function(response) {
                    $('#percent_off').val(response.percent_off);
                    document.getElementById('discount_text').innerHTML = "Discount " + response.percent_off + "%";
                    document.getElementById('discounted_amount').innerHTML = "{{@$currency}}" + response.event_price;

                    // Change some amount here
                    console.log('okay');
                    console.log(response);

                    $('#book_now').removeAttr('disabled');
                },
                error: function(error) {
                    console.log(error);
                    document.getElementById('discount_text').innerHTML = "";
                    document.getElementById('discounted_amount').innerHTML = "Invalid Coupon";

                    $('#book_now').removeAttr('disabled');
                }
            });

            console.log(self.val());

        }, 1000));

        $('#editContentModal').on('show.bs.modal', function(event) {

            var button = $(event.relatedTarget) // Button that triggered the modal
            var contentField = button.data('contentField') // Extract info from data-* attributes
            var clubId = button.data('clubId') // Extract info from data-* attributes

            var content = button.data('content')

            var label = button.data('label')

            var hasImage = button.data('hasImage')

            var hasVideo = button.data('hasVideo')

            var hasFileUpload = button.data('hasFileUpload')

            var fileUploadOnly = button.data('fileUploadOnly')

            var videoOnly = button.data('videoOnly')

            $('#image_url').hide();

            if (hasImage == true) {
                // show image field URL
                $('#image_url').show();
            }

            $('#file_upload').hide();

            if (hasFileUpload == true) {
                $('#file_upload').show();
            }

            $('#video_url').hide();

            if (hasVideo == true) {
                // show image field URL
                $('#video_url').show();
            }

            $('#editContentLabel').text('Edit ' + label);

            $('#content_field').val(contentField);
            $('#club_id').val(clubId);

            var trix = document.querySelector("trix-editor")
            trix.style.display = 'block';
            //element.editor // is a Trix.Editor instance

            if (content != undefined) {
                // tinymce.activeEditor.setContent(content);
                trix.value = "";
                trix.editor.insertHTML(content)
            }

            if (content == undefined || fileUploadOnly == true || videoOnly == true) {
                trix.style.display = 'none';
                // tinymce.activeEditor.setContent("");
                // tinymce.activeEditor.hide();
            }
        });

        $('#deleteImageModal').on('show.bs.modal', function(event) {

            var button = $(event.relatedTarget) // Button that triggered the modal

            var imageId = button.data('imageId')

            var targetSection = button.data('targetSection')

            // alert(targetSection);


            $('#delete_image_id').val(imageId);
        });

        $('#deleteImageForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('delete.image') }}",
                type: 'POST',
                data: {
                    id: $('#delete_image_id').val(),
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#deleteImageModal').modal('hide');
                    location.reload();
                },
                error: function(error) {
                    console.log(error);
                    $('#deleteImageModal').modal('hide');
                    location.reload();
                }
            });
        });

        $('#editDirectorImageModal').on('show.bs.modal', function(event) {

            var button = $(event.relatedTarget) // Button that triggered the modal
            var contentField = button.data('contentField') // Extract info from data-* attributes
            var clubId = button.data('clubId') // Extract info from data-* attributes

            var content = button.data('content')

            // var label = button.data('label')

            // $('#editContentLabel').text('Edit ' + label);
            $('#director_image').val(content);

            $('#content_field').val(contentField);
            $('#club_id').val(clubId);
        });

        $('#postEditDirectorImageForm').on('submit', function(e) {
            e.preventDefault();

            var data = {
                "content_field": $('#content_field').val(),
                "club_id": $('#club_id').val(),
                "content": $('#director_image').val(),
                "_token": "{{ csrf_token() }}"
            };

            $.ajax({
                url: "{{ route('update.club.data') }}",
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {},
                error: function(error) {
                    console.log(error);
                    $('#editDirectorImageModal').modal('hide');
                    location.reload();
                }
            });
        });
    });

    function logOut() {
        var cookies = document.cookie.split(";");
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i];
            var eqPos = cookie.indexOf("=");
            var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
        }
        var geturl = window.location.pathname;
        geturl = geturl.substring(1);
        document.cookie = "clubs=" + geturl;

        window.location.href = "https://lobby.globalwomanclub.com/oauth?app_id=dd94cac1a7f8f47192b6&logout=false";
    }

    function checkCouponCode() {
        var coupon_code = document.getElementById('londonCouponCode').value;
        var club_form = document.getElementById('clubs').value;
        if (club_form == "London") {
            if (coupon_code == "LONDON50") {
                var memberStatus = "<?php echo $member ?>";
                console.log("memberStatus", memberStatus);
                var finalPrice = 25;
                if (memberStatus == "1") {
                    finalPrice = 13;
                }
                document.getElementById('londonCouponCodeErrorMsg').style.display = "none";

                document.getElementById('londonCouponCodeSuccessMsg').style.display = "block";
                document.getElementById('londonCouponCodeSuccessMsg').innerHTML = "Coupon code applied successfully!";

                var event_id_form = document.getElementById('event_id').value;
                var club_form = document.getElementById('clubs').value;
                var crftoken = document.getElementById('token').value;
                var form = new FormData();
                form.append("event_id", event_id_form);
                form.append("clubs", club_form);
                form.append("price_data", finalPrice);
                form.append("_token", crftoken);


                var settings = {
                    "url": "/postBuyNow",
                    "method": "POST",
                    "timeout": 0,
                    "processData": false,
                    "mimeType": "multipart/form-data",
                    "contentType": false,
                    "data": form
                };
                $.ajax(settings).done(function(response) {
                    var obj = JSON.parse(response);
                    var status = obj.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        window.location.href = "{{ config('app.url') }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
                    } else {
                        console.log(obj);
                    }
                });
            } else {
                document.getElementById('londonCouponCodeErrorMsg').style.display = "block";
                document.getElementById('londonCouponCodeErrorMsg').innerHTML = "Please enter valid coupon code";

                document.getElementById('londonCouponCodeSuccessMsg').style.display = "none";
            }
        } else {
            if (coupon_code == "friends20") {
                document.getElementById('londonCouponCodeErrorMsg').style.display = "none";

                document.getElementById('londonCouponCodeSuccessMsg').style.display = "block";
                document.getElementById('londonCouponCodeSuccessMsg').innerHTML = "Coupon code applied successfully!";

                var event_id_form = document.getElementById('event_id').value;
                var club_form = document.getElementById('clubs').value;
                var crftoken = document.getElementById('token').value;
                var form = new FormData();
                form.append("event_id", event_id_form);
                form.append("clubs", club_form);
                form.append("price_data", 76);
                form.append("_token", crftoken);

                var settings = {
                    "url": "/postBuyNow",
                    "method": "POST",
                    "timeout": 0,
                    "processData": false,
                    "mimeType": "multipart/form-data",
                    "contentType": false,
                    "data": form
                };
                $.ajax(settings).done(function(response) {
                    var obj = JSON.parse(response);
                    var status = obj.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        window.location.href = "{{ config('app.url') }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
                    } else {
                        console.log(obj);
                    }
                });
            } else if (coupon_code == "friends50") {
                document.getElementById('londonCouponCodeErrorMsg').style.display = "none";

                document.getElementById('londonCouponCodeSuccessMsg').style.display = "block";
                document.getElementById('londonCouponCodeSuccessMsg').innerHTML = "Coupon code applied successfully!";

                var event_id_form = document.getElementById('event_id').value;
                var club_form = document.getElementById('clubs').value;
                var crftoken = document.getElementById('token').value;
                var form = new FormData();
                form.append("event_id", event_id_form);
                form.append("clubs", club_form);
                form.append("price_data", 35);
                form.append("_token", crftoken);

                var settings = {
                    "url": "/postBuyNow",
                    "method": "POST",
                    "timeout": 0,
                    "processData": false,
                    "mimeType": "multipart/form-data",
                    "contentType": false,
                    "data": form
                };
                $.ajax(settings).done(function(response) {
                    var obj = JSON.parse(response);
                    var status = obj.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        window.location.href = "{{ config('app.url') }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
                    } else {
                        console.log(obj);
                    }
                });
            } else if (coupon_code == "family") {
                document.getElementById('londonCouponCodeErrorMsg').style.display = "none";

                document.getElementById('londonCouponCodeSuccessMsg').style.display = "block";
                document.getElementById('londonCouponCodeSuccessMsg').innerHTML = "Coupon code applied successfully!";

                var event_id_form = document.getElementById('event_id').value;
                var club_form = document.getElementById('clubs').value;
                var crftoken = document.getElementById('token').value;
                var form = new FormData();
                form.append("event_id", event_id_form);
                form.append("clubs", club_form);
                form.append("price_data", 19);
                form.append("_token", crftoken);

                var settings = {
                    "url": "/postBuyNow",
                    "method": "POST",
                    "timeout": 0,
                    "processData": false,
                    "mimeType": "multipart/form-data",
                    "contentType": false,
                    "data": form
                };
                $.ajax(settings).done(function(response) {
                    var obj = JSON.parse(response);
                    var status = obj.status;
                    if (status == "success") {
                        var eventIdDataId = "<?php echo $event->id ?>";
                        window.location.href = "{{ config('app.url') }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
                    } else {
                        console.log(obj);
                    }
                });
            } else {
                document.getElementById('londonCouponCodeErrorMsg').style.display = "block";
                document.getElementById('londonCouponCodeErrorMsg').innerHTML = "Please enter valid coupon code";

                document.getElementById('londonCouponCodeSuccessMsg').style.display = "none";
            }
        }
    }

    // Coupon Code Form
    $(document).ready(function() {
        // var name = document.getElementById("couponCodeName").value;
        // var email = document.getElementById("couponCodeEmail").value;
        // var phone = document.getElementById("couponCodeTel").value;


    });


    function sendEventData(ePrice) {
        @if($is_allowed_attend_event == false)
            showBookingErrorModal("You've already attended an event with us and can't book another In-Person ticket on your current plan. Please upgrade to Entrepreneur or Influencer and contact support@globalwomanclub.com for more information.", 'Unable to complete booking');
            return false;
        @endif
        var event_id_form = document.getElementById('event_id').value;
        var club_form = document.getElementById('clubs').value;
        var crftoken = document.getElementById('token').value;
        var is_hybrid = 0;
        if (document.getElementById('is_hybrid') == null) {
            is_hybrid = 0;
        } else {
            is_hybrid = document.getElementById('is_hybrid').value;
        }
        var form = new FormData();
        form.append("event_id", event_id_form);
        form.append("clubs", club_form);
        form.append("price_data", ePrice);
        form.append("is_hybrid", is_hybrid);
        form.append("_token", crftoken);

        var settings = {
            "url": "/postBuyNow",
            "method": "POST",
            "timeout": 0,
            "processData": false,
            "mimeType": "multipart/form-data",
            "contentType": false,
            "data": form
        };
        $.ajax(settings).done(function(response) {
            var obj = JSON.parse(response);
            var status = obj.status;
            if (status == "success") {
                var eventIdDataId = "<?php echo $event->id ?>";
                window.location.href = "{{ request()->root() }}/e/" + eventIdDataId + "/checkout/payment?is_embedded=0";
            } else {
                if (obj && obj.message) {
                    showBookingErrorModal(obj.message);
                }
                console.log(obj);
            }
        });

    }
</script>