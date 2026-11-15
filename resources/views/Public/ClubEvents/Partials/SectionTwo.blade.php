<!-- Public.ClubEvents.Partials.SectionTwo -->
<section id="section-two" class="section-two" style="background-color:#f1f4f8">

  <div class="container">
    <div class="row">
	@php
		$eventImage = App\Models\EventImage::where('event_id', '=', $event->id)->get();
	@endphp
      <div class="col-lg-6 col-sm-12 align-self-center">
		@foreach($eventImage as $img)
			<img src="https://davxegdvkjbks.cloudfront.net/{{$img->image_path}}" alt="">
		@endforeach
		<br>
		<br>
		@if($club=='London-mayfair')
		<p style="font-family:'loto'; text-align: center;font-size:18px">Not a Global Woman member yet?
Expect to receive some incredible incentives if you join the London Mayfair Club Event!</p>
	@endif

      </div>
      <div class="col-lg-6 col-sm-12 align-self-center">
        <div class="two-content">
		@if($club=='London-mayfair')
			 <h1 style="font-family:'loto'; text-align: center;font-size:24px;"><b>{{$event->title}}</b></h1>
			 <p class="m-0" style="font-family:'loto';color:#da2926;"><b>Date:</b> {{date('l  jS \ F Y',strtotime($event->start_date))}}</p>
			 <p class="m-0" style="font-family:'loto';color:#da2926;"><b>Time:</b> {{date('h:i A',strtotime($event->start_date))}} - {{date("h:i A", strtotime($event->end_date))}} (London Time)</p>
			 <p class="m-0"style="font-family:'loto';color:#da2926;"><b>Venue:</b> {{$event->location}}</p>
		 <br>

		@else
			<h1>{{$event->title}} ({{$event->event_ttype}})</h1>

			@if($event->event_ttype=='Online')
				@if($club == 'Orange-County')
					<p class="date-time"><i class="far fa-calendar"></i> <b>{{date('l - jS \ F Y h:i A - ',strtotime($event->start_date))}} {{date("h:i A", strtotime($event->end_date))}}  (PDT Time Zone)   </b> </p>
				@else
					<p class="date-time"><i class="far fa-calendar"></i> <b>{{date('l - jS \ F Y h:i A - ',strtotime($event->start_date))}} {{date("h:i A", strtotime($event->end_date))}}  ({{  str_replace('Accra', 'UK', $club)  }} Time)   </b> </p>
				@endif
			@else
				@if($club == 'Orange-County')
					<p class="date-time"><i class="far fa-calendar"></i> <b>{{date('l - jS \ F Y h:i A - ',strtotime($event->start_date))}} {{date("h:i A", strtotime($event->end_date))}} (PDT Time Zone) <br>{{$event->location}}<br></b></p>
				@else
					<p class="date-time"><i class="far fa-calendar"></i> <b>{{date('l - jS \ F Y h:i A - ',strtotime($event->start_date))}} {{date("h:i A", strtotime($event->end_date))}} ({{  str_replace('Accra', 'UK', $club)  }} Time) <br>{{$event->location}}<br></b></p>
				@endif

			@endif

            @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
            <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="description" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->description }}" data-label="Event Description">
                Edit
            </a>
            @endif

            @if (isset($clubData) && $clubData->description != '' && $clubData->description != null )
                <!-- Description here -->
                {!! $clubData->description !!}
            @else
                @if(isset($sectionTwoDescription))
                <p>{{ $sectionTwoDescription }}</p>
                @else
                <p>It's great to be able to meet in-person again for the networking experience, where you can feel the human vibes.We bring such a powerful and empathic energy, where we can do business, connect, build relationships and create fruitful collaborations.</p>
                @endif
            @endif
		@endif

        @if($club=='Zurich')
          <p>We will bring you such a powerful and engaging experience, so that you can get connected, start building relationships and create new business partnerships.</p>
		@elseif($club=='birmingham')
		 <p>It's great to be able to meet in-person again for the networking experience, where you can feel the human vibes.We bring such a powerful and empathic energy, where we can do business, connect, build relationships and create fruitful collaborations.</p>
		 @elseif($club=='London-mayfair')
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
		@elseif($event->event_ttype=='Online')
			<p>Connect with members and non members from other clubs and locations around the world, in a warm and welcoming community of women seeking to grow their business, find new clients, be inspired, and listen to some great speakers.</p>
		@elseif($club=='London')
		<p>Get ready to step into a world of inspiration, networking and wisdom with Global Woman Club. We bring together entrepreneurs, startups, leaders, influencers and investors.</p>
        <p>This isn't just an event; it's an experience, an opportunity that opens doors for you and promises to ignite your imagination, spark ideas, and make your dreams come true.</p>
		@elseif($club=='Florence')
			<p>This event is exclusive for people who are in business. Global Woman is a place to connect, network, brainstorm and create a supportive environment for each other. We bring a powerful and empathic energy, where we can do business, connect, build relationships and create fruitful collaborations.</p>
        @elseif($club=='Newyork')
			<p>Get ready to step into a world of inspiration, networking and wisdom with Global Woman Club. We bring together entrepreneurs, startups, leaders, influencers and investors.</p>
            <p>This isn't just an event; it's an experience, an opportunity that opens doors for you and promises to ignite your imagination, spark ideas, and make your dreams come true.</p>
		@else
            @if(isset($sectionTwoDescription))
            <p>{{ $sectionTwoDescription }}</p>
            @else
			<p>It's great to be able to meet in-person again for the networking experience, where you can feel the human vibes.We bring such a powerful and empathic energy, where we can do business, connect, build relationships and create fruitful collaborations.</p>
            @endif
		@endif

          <a href="#section-ticket-book" class="btn bg-red text-white">Book Your Ticket Here</a>
        </div>

      </div>
    </div>
    <br>
    <br>
	
     <p style="font-family:'loto';font-size:22px">
        <b>About the event:</b>  If you have been following Global Woman Club and the work we do around the world to empower women you may know that we focus in two areas.
<br>
<ol>
	<li><p style="font-family:'loto';font-size:22px">We help women build their confidence </p>
</li>
	<li><p style="font-family:'loto';font-size:22px">We help women make their own money </p>
</li>
</ol>

<p style="font-family:'loto';font-size:22px">
	How do we do it? We have our mantra: If You want to empower a woman, give her a microphone.
How can the microphone empower women? Well, we know that in order to start their own business they need more confidence. And confidence is not something you can learn by reading books, you need to take action. This is exactly what we do, we encourage women to take a microphone and come on stage to share with us: What’s their purpose? What’s their vision? If they don’t know it yet, we help them to find it. The mic has so much power, and by coming on stage, they stand in their power. That power can also reflect success toward their projects or new ventures. This in return can reflect into real business with other like minded women. We bring them together with networking events and just in a short time they see the difference.
</p>
        </p>
  </div>
    @if(isset($otherInfo))
        @include($otherInfo)
    @endif
</section>
