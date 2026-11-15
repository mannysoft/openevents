<div class="col-md-6">
    <div class="grid-box">
        <div type="ticket" class="widget --flex-column" @if(@$vipTicket==true) style="filter: drop-shadow(1px 10px 3px rgba(255, 1, 0, 2));box-shadow: 0px 1px 5px #ff0000, 0px -1px 5px #ff0000;" @endif>
            <div class="top --flex-column">

                <div class="bandname -bold">@if(isset($ticketText)) {{ $ticketText}} @else In-Person Networking Event @endif</div>
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
                        @if (isset($isMemberEvent))
                        <div class="location -bold">Member Event</div>
                        @else
                        <div class="location -bold">{{ pricing_label(@$vipTicket, 0) }}</div>
                        @endif

                        <br>
                        <div class="location -bold">{{ pricing_label(@$vipTicket, 1) }}</div>
                    </div>
                    <div class="price --flex-column">
                        <div class="label">Price</div>
                        <div class="cost -bold text-right">
                            @php
                            $event_price = event_price(@$vipTicket, $event, $member);
                            @endphp
                            <span>{{ event_label(@$vipTicket, $event, false) }}</span>
                        </div>

                        <br>
                        <div class="cost -bold text-right">
                            <span>{{ event_label(@$vipTicket, $event, true) }}</span>
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
                        <input type="hidden" class="form-control" id="price_data" name="price_data" value="{{event_price(@$vipTicket, $event, $member)}}">
                        <input type="hidden" class="form-control" id="purchase_email" name="purchase_email" value="{{$email}}">
                        @if(@$vipTicket == true)
                        <button type="submit" class="book" value="Submit">BOOK NOW</button>
                        <input type="hidden" id="is_vip" name="is_vip" value="1">
                        @else
                        <button type="button" class="book" value="Submit" onclick="sendEventData(<?php echo event_price(@$vipTicket, $event, $member); ?>)">BOOK NOW</button>
                        @endif
                    </form>
                </div>

                @endif
            </div>
        </div>
    </div>
</div>