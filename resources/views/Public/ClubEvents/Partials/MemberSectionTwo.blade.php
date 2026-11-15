<section id="section-two" class="section-two" style="background-color:#f1f4f8">

  <div class="container">
    <div class="row">

      <div class="col-lg-6 col-sm-12 align-self-center">
			
        <img src="{{ $event->postImage }}" />
		
		<br>
		<br>

      </div>

      <div class="col-lg-6 col-sm-12 align-self-center">
        <div class="two-content">
	
        <h1>{{$event->postTitle}}</h1>
        <p class="date-time"><i class="far fa-calendar"></i> <b>Date: {{date('l  jS \ F Y',strtotime($event->start_date))}}</b></p>
        <p class="date-time"><i class="far fa-calendar"></i> <b>Time: {{date('h:i A',strtotime($event->start_time))}} - {{date("h:i A", strtotime($event->end_time))}} ({{ $event->timezone }})</b></p>
	
		<p style="font-family:'loto';font-size:22px">{!! $event->postDescription !!}</p>
	
          <a href="#section-ticket-book" class="btn bg-red text-white">Book Your Ticket Here</a>
        </div>


      </div>
    </div>
    <br>
    <br>
  </div>
</section>
