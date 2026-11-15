<section id="section-one" class="section-one" style="background-color:rgba(16, 16, 16, 0.83)">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 col-sm-12">
		@if($event->id=="732")
				<h1>Full Day Conference <br/>{{date('l jS \ F',strtotime($event->start_date))}}</h1>
			
		@else
			@if($event->lobby1_live=='1')
				<h1>{{$event->event_ttype}} Networking Event <br/>{{date('l jS \ F',strtotime($event->start_date))}}</h1>
			@else
				<h1>{{$event->title}} <br/>{{date('l jS \ F',strtotime($event->start_date))}}</h1>
			@endif
		@endif
		@if($club=='london-aug1')
			<p class="m-0">Date : Friday 12th Aug - Sunday 14th Aug  </p>
		@elseif($club=='london-aug2')
		  <p class="m-0">Date : Friday 26th Aug - Sunday 28th Aug  </p>
		@else
			<p class="m-0">Date: {{date('l  jS \ F Y',strtotime($event->start_date))}}</p>
		@endif
		
		@if($event->event_ttype=='Online')
			@if($club == 'Orange-County')
				<p class="m-0">Time: {{date('h:i A',strtotime($event->start_date))}} - {{date("h:i A", strtotime($event->end_date))}} (PDT Time Zone)</p>
			@elseif($club=='London-mayfair')
				<p class="m-0">Time: {{date('h:i A',strtotime($event->start_date))}} - {{date("h:i A", strtotime($event->end_date))}} (London Time)</p>
			@else
				<p class="m-0">Time: {{date('h:i A',strtotime($event->start_date))}} - {{date("h:i A", strtotime($event->end_date))}} ({{ $club }} Time)</p>
                <!-- Club Time 1 -->
			@endif
		@else
			@if($club == 'Orange-County')
				<p class="m-0">Time: {{date('h:i A',strtotime($event->start_date))}} - {{date("h:i A", strtotime($event->end_date))}} (PDT Time Zone)</p>
			@elseif($club=='London-mayfair')
				<p class="m-0">Time: {{date('h:i A',strtotime($event->start_date))}} - {{date("h:i A", strtotime($event->end_date))}} (London Time)</p>
			@else
				<p class="m-0">Time: {{date('h:i A',strtotime($event->start_date))}} - {{date("h:i A", strtotime($event->end_date))}} ({{  str_replace('accra', 'UK', $club )  }} Time)</p>       
                <!-- Club Time 2 -->
			@endif
			<p class="m-0">{{$event->location}}</p>
			@if($event->id == '886')
				<p class="m-0">VIP Guest List: successful business owners, investors, media and TV personalities</p>
			@endif
			
		@endif
      </div>
      <div class="col-lg-6 col-sm-12 text-center align-self-center">
        <a href="#section-ticket-book" class="btn bg-red text-white">Book Your Ticket Here</a>
        <!-- Book -->
      </div>
    </div>
  </div>
</section>

