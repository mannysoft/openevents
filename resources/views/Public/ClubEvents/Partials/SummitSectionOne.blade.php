<!-- Public.ClubEvents.Partials.SectionOne -->
<section id="section-one" class="section-one" style="background-color:rgba(16, 16, 16, 0.83)">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-sm-12 @if(isset($textColorClass)) {{ $textColorClass }} @endif">
                <h1>{{$event->title}} <br />{{date('l jS \ F',strtotime($event->start_date))}}</h1>

                @if ($multiDate)
                <p class="m-0">Date: {{date('l jS \ F Y',strtotime($event->start_date))}} to {{date('l jS \ F Y',strtotime($event->end_date))}}</p>
                @else
                <p class="m-0">Date: {{date('l jS \ F Y',strtotime($event->start_date))}}</p>
                @endif
                <p class="m-0">{{ $event->location }}</p>

            </div>
            <div class="col-lg-6 col-sm-12 text-center align-self-center">
                <a style="margin-top: 10px;" href="#section-ticket-book" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">Book Your Ticket Here</a>
            </div>
        </div>
    </div>
</section>