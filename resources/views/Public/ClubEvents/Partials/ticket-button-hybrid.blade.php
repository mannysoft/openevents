<div class="row justify-content-center">
    <a href="{{ route('register.url') }}" target="OpenLobbyLogin"></a>
    <div class="col-md-6">
        <div class="grid-box">
            <div type="ticket" class="widget --flex-column">
                <div class="top --flex-column">

                    <div class="bandname -bold">Online Networking Event</div>
                    <div class="tourname">{{$club}}</div>
                    @if($event->vimage == '')

                    @php
                    $eventImage = App\Models\EventImage::where('event_id', '=', $event->id)->get();

                    @endphp
                    @foreach($eventImage as $img)
                    <img src="https://davxegdvkjbks.cloudfront.net/{{$img->image_path}}" alt="Online Networking Event Image">
                    @endforeach

                    @else
                    <img src="https://davxegdvkjbks.cloudfront.net/{{$event->vimage}}" alt="Online Networking Event Image">
                    @endif
                    <div class="deetz --flex-row">
                        <div class="event --flex-column">
                            <div class="date">{{date('jS \ F Y',strtotime($event->start_date))}}</div>
                            <div class="location -bold">Non-Member</div>

                            <br>
                            <div class="location -bold">Member</div>
                        </div>

                        <div class="price --flex-column">
                            <div class="label">Price</div>
                            <div class="cost -bold text-right">
                                @php
                                $event_price = event_price(false, $event, $member, false);
                                @endphp
                                <span>{{ event_label(false, $event, false, false) }}</span>
                            </div>

                            <br>
                            <div class="cost -bold text-right">
                                <span>{{ event_label(false, $event, true, false) }}</span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="rip"></div>
                <div class="bottom --flex-row">
                    <div class="barcode"></div>
                    @if($oldEvent=="1")
                    <a class="book bg-green" href="javascript:void(0);">Booking Closed</a>

                    @elseif($purchased==1 && ($joinPurchasedAmount == $event->mprice || $joinPurchasedAmount == $event->nmprice))
                    <a class="book bg-green" href="javascript:void(0);">BOOKED</a>
                    @else
                    <div>
                        <form id="postBuyForm11" action="#" method="post">
                            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                            <input type="hidden" class="form-control" id="event_id" name="event_id" value="{{$event->id}}">
                            <input type="hidden" class="form-control" id="clubs" name="clubs" value="{{$club}}">
                            <input type="hidden" class="form-control" id="price_data" name="price_data" value="{{$event_price}}">
                            <input type="hidden" class="form-control" id="purchase_email" name="purchase_email" value="{{$email}}">
                            <button type="submit" class="book" value="Submit">BOOK NOW</button>
                        </form>
                    </div>

                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="grid-box">
            <div type="ticket" class="widget --flex-column">
                <div class="top --flex-column">

                    <div class="bandname -bold">In-Person Networking Event</div>
                    <div class="tourname">{{$club}}</div>
                    @if($event->iimage == '')

                    @php
                    $eventImage = App\Models\EventImage::where('event_id', '=', $event->id)->get();

                    @endphp
                    @foreach($eventImage as $img)
                    <img src="https://davxegdvkjbks.cloudfront.net/{{$img->image_path}}" alt="In-Person Networking Event Image">
                    @endforeach

                    @else
                    <img src="https://davxegdvkjbks.cloudfront.net/{{$event->iimage}}" alt="In-Person Networking Event Image">
                    @endif
                    <div class="deetz --flex-row">
                        <div class="event --flex-column">
                            <div class="date">{{date('jS \ F Y',strtotime($event->start_date))}}</div>
                            <div class="location -bold">Non-Member</div>

                            <br>
                            <div class="location -bold">Member</div>
                        </div>

                        <div class="price --flex-column">
                            <div class="label">Price</div>
                            <div class="cost -bold text-right">
                                @php
                                $event_price = event_price(false, $event, $member, true);
                                @endphp
                                <span>{{ event_label(false, $event, false, true) }}</span>
                            </div>

                            <br>
                            <div class="cost -bold text-right">
                                <span>{{ event_label(false, $event, true, true) }}</span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="rip"></div>
                <div class="bottom --flex-row">
                    <div class="barcode"></div>
                    @if($oldEvent=="1")
                    <a class="book bg-green" href="javascript:void(0);">Booking Closed</a>

                    @elseif($purchased==1 && ($joinPurchasedAmount == $event->hmprice || $joinPurchasedAmount == $event->hnmprice))
                    <a class="book bg-green" href="javascript:void(0);">BOOKED</a>
                    @else
                    <div>
                        <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                        <input type="hidden" class="form-control" id="event_id" name="event_id" value="{{$event->id}}">
                        <input type="hidden" class="form-control" id="clubs" name="clubs" value="{{$club}}">
                        <input type="hidden" class="form-control" id="price_data" name="price_data" value="{{$event_price}}">
                        <input type="hidden" class="form-control" id="purchase_email" name="purchase_email" value="{{$email}}">
                        <input type="hidden" id="is_hybrid" name="is_hybrid" value="1">
                        <button type="button" class="book" value="Submit" onclick="sendEventData(<?php echo $event_price; ?>)">BOOK NOW</button>

                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>