<div class="row justify-content-center">
    @if($canBookRight)
    {{-- Below use for show ticket amount right away  --}}
    <div class="col-md-12 col-lg-10">
    @else
    <div class="col-md-6">
    @endif
        <div class="grid-box">
            <div type="ticket" class="widget --flex-column">
                <div class="top --flex-column">
                    @if($event->lobby1_live=='0')
                    <div class="bandname -bold">{{$event->title}} BOOK YOUR TICKET HERE</div>
                    @else
                    <div class="bandname -bold">@if(isset($ticketText)) {{ $ticketText}} @else In-Person Networking @endif</div>
                    @endif

                    @if($club == 'Accra')
                    <div class="tourname">Global Woman Club Online</div>
                    @elseif($club=='London-mayfair')
                    <div class="tourname">London Mayfair</div>
                    @else
                    <div class="tourname">{{$club}}</div>
                    @endif

                    @php
                    $eventImage = App\Models\EventImage::where('event_id', '=', $event->id)->get();

                    @endphp
                    @foreach($eventImage as $img)
                    <img src="https://davxegdvkjbks.cloudfront.net/{{$img->image_path}}" alt="">
                    @endforeach
                    @if(isset($event->postImage))
                    @if($event->postImage != null)
                    <!-- This is for non premium member event -->
                    <!-- <img src="{{ $event->postImage }}" alt=""> -->
                    @endif
                    @endif
                    <div class="deetz --flex-row">
                        <div class="event --flex-column">
                            <div class="date">{{date('jS \ F Y',strtotime($event->start_date))}}</div>
                            @if (isset($isMemberEvent))
                            <div class="location -bold">Member Event</div>
                            @else
                            <div class="location -bold">Non-Member</div>
                            @endif

                            <br>
                            <div class="location -bold">Member</div>
                        </div>
                        <div class="price --flex-column">
                            <div class="label">Price</div>
                            <div class="cost -bold text-right">
                                @php
                                $event_price = event_price(false, $event, $member);
                                @endphp
                                <span>{{ event_label(false, $event, false) }}</span>
                            </div>

                            <br>
                            <div class="cost -bold text-right">
                                <span>{{ event_label(false, $event, true) }}</span>
                            </div>

                        </div>
                    </div>
                    <div class="divider"></div>

                    @if(!isset($hideCoupon))
                    {{-- <div class="deetz --flex-row">
                        <div class="event --flex-column">
                            <div class="location -bold">
                                <input name="coupon_code" id="coupon_code" type="text" class="form-control" placeholder="Enter Coupon Code" />
                            </div>
                        </div>
                        <div class="price --flex-column">
                            <div class="cost -bold text-right">
                                <!-- <span class="text-red">$30</span> -->
                            </div>
                        </div>
                    </div>
                    <div class="divider"></div> --}}
                    @endif

                    <div class="deetz --flex-row" id="discount">
                        <div class="event --flex-column">
                            <div class="location -bold" id="discount_text"></div>
                        </div>
                        <div class="price --flex-column">
                            <div class="cost -bold text-right">
                                <span class="text-red"><span id="discounted_amount"></span></span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="rip"></div>
                <div class="bottom --flex-row">
                    <div class="barcode"></div>
                    @if($oldEvent=="1")
                    <a class="book bg-green" href="javascript:void(0);">Booking Closed</a>
                    @elseif($purchased==1)
                    <a class="book bg-green" href="javascript:void(0);">BOOKED</a>
                    @else
                    <div>

                        <div>
                            <a href="{{ route('register.url') }}" target="OpenLobbyLogin"></a>
                        </div>

                        <form id="postBuyForm11" action="#" method="post">
                            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                            <input type="hidden" class="form-control" id="event_id" name="event_id" value="{{$event->id}}">
                            <input type="hidden" class="form-control" id="clubs" name="clubs" value="{{$club}}">
                            <input type="hidden" class="form-control" id="purchase_email" name="purchase_email" value="{{$email}}">
                            <input type="hidden" class="form-control" id="price_data" name="price_data" value="{{$event_price}}">
                            <input type="hidden" class="form-control" id="percent_off" name="percent_off" value="">
                            <input type="hidden" class="form-control" id="event_price" name="event_price" value="{{$event_price}}">
                            <input type="hidden" class="form-control" id="is_member_event" name="is_member_event" value="{{@$isMemberEvent}}">
                            <button id="book_now" type="submit" class="book" value="Submit">BOOK NOW</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- <livewire:speaking-slot :event="$event" :purchased="$purchased" /> --}}
    </div>
</div>