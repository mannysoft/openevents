<section id="section-one" class="section-one" style="background-color:rgba(16, 16, 16, 0.83)">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 col-sm-12">
		<h1>{{$event->postTitle}} <br/>{{date('l jS \ F',strtotime($event->start_date))}}</h1>
		<p class="m-0">Date: {{date('l  jS \ F Y',strtotime($event->start_date))}}</p>
        <p class="m-0">Time: {{date('h:i A',strtotime($event->start_time))}} - {{date("h:i A", strtotime($event->end_time))}} ({{ $event->event_timezone_format }})</p>
      </div>
      <div class="col-lg-6 col-sm-12 text-center align-self-center">
        <!-- <a href="#section-ticket-book" class="btn bg-red text-white">Book Your Ticket Here</a> -->
      </div>
    </div>
  </div>
</section>

