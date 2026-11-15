<!-- Public.ClubEvents.Partials.DefaultSectionTwo -->
<section id="section-two" class="section-two" style="background-color:#f1f4f8">

    <div class="container">
        <div class="row">
            @php
            $eventImage = App\Models\EventImage::where('event_id', '=', $event->id)->get();
            @endphp
            <div class="col-lg-6 col-sm-12 align-self-center">
                @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
                <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="after_ticket_image" data-club-id="{{ $clubData->id }}" data-has-file-upload="true" data-file-upload-only="true" data-label="Image">
                    Edit Image
                </a>
                @endif
                @if (isset($clubData) && @$clubData->after_ticket_image != '' && @$clubData->after_ticket_image != null )
                <img src="{{ $clubData->after_ticket_image }}" class="card-img" alt="">
                @else
                    @foreach($eventImage as $img)
                    <img src="https://davxegdvkjbks.cloudfront.net/{{$img->image_path}}" alt="">
                    @endforeach
                @endif
                <br>
                <br>
                @if(isset($globalmanclub) && $globalmanclub == true)
                {{-- Hide Message --}}
                @else
                <p style="font-family:'loto'; text-align: center;font-size:18px">Not a Global Woman member yet?
                    Expect to receive some incredible incentives if you join the Global Woman Club Event!</p>
                @endif
                
            </div>

            <div class="col-lg-6 col-sm-12 align-self-center">
                <div class="two-content">

                    @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
                    <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="description" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->description }}" data-label="Event Description">
                        Edit
                    </a>
                    @endif

                    <!-- Description here -->
                    @if (isset($clubData) && $clubData->description != '' && $clubData->description != null )
                    {!! $clubData->description !!}
                    @else
                    <p style="font-family:'loto';font-size:16px">Whether you're a seasoned professional seeking to expand your network or an aspiring
                        entrepreneur, Global Woman Club London Mayfair will support your business growth and provide
                        amazing networking opportunities with like-minded business owners and investors.
                        <br>
                        <br>
                        Join us if you
                        want to build relationships, make valuable connections and learn from inspiring guest speakers
                        how to overcome challenges, navigate through uncharted territories and build thriving enterprises
                        from the ground up.
                    </p>
                    @endif
                    <a href="#section-ticket-book" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">Book Your Ticket Here</a>
                </div>


            </div>
        </div>
        <br>
        <br>

        @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
        <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="about_event" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->about_event }}" data-label="About Event">
            Edit
        </a>
        @endif

        @if (isset($clubData) && $clubData->about_event != '' && $clubData->about_event != null )
        {!! $clubData->about_event !!}
        @else
        <p style="font-family:'loto';font-size:22px">
            <b>About the event:</b> If you have been following Global Woman Club and the work we do around the world to empower women you may know that we focus in two areas.
            <br>
        <ol>
            <li>
                <p style="font-family:'loto';font-size:22px">We help women build their confidence </p>
            </li>
            <li>
                <p style="font-family:'loto';font-size:22px">We help women make their own money </p>
            </li>
        </ol>

        <p style="font-family:'loto';font-size:22px">
            How do we do it? We have our mantra: If You want to empower a woman, give her a microphone.
            How can the microphone empower women? Well, we know that in order to start their own business they need more confidence. And confidence is not something you can learn by reading books, you need to take action. This is exactly what we do, we encourage women to take a microphone and come on stage to share with us: What’s their purpose? What’s their vision? If they don’t know it yet, we help them to find it. The mic has so much power, and by coming on stage, they stand in their power. That power can also reflect success toward their projects or new ventures. This in return can reflect into real business with other like minded women. We bring them together with networking events and just in a short time they see the difference.
        </p>
        @endif
    </div>

    @if(isset($otherInfo))
    @include($otherInfo)
    @endif
</section>