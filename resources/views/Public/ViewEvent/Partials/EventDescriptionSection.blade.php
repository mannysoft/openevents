<section id="details" class="container">
  <h1 class="section_head">
      @lang("Public_ViewEvent.event_details")
  </h1>
    <div class="row">
        <!-- @php
            $descriptionColSize =  $event->images->count()
                && in_array($event->event_image_position, ['left', 'right'])
                ? '7' : '12';
        @endphp

        @if ($event->images->count() && $event->event_image_position == 'left')
            <div class="col-md-5">
                <div class="content event_poster">
                    <img alt="{{$event->title}}" src="{{config('attendize.cdn_url_user_assets').'/'.$event->images->first()['image_path']}}" property="image">
                </div>
            </div>
        @endif
        @if ($event->images->count() && $event->event_image_position == 'before')
            <div class="col-md-12" style="margin-bottom: 20px">
                <div class="content event_poster">
                    <img alt="{{$event->title}}" src="{{config('attendize.cdn_url_user_assets').'/'.$event->images->first()['image_path']}}" property="image">
                </div>
            </div>
        @endif -->

        <div class="col-md-{{ $descriptionColSize }}">
            <div property="description">
				     {!!html_entity_decode($event->description)!!}
            </div>
            <div class="countdown_wrap">
              <h6 class="countdown_title text-center">EVENT WILL START IN</h6>
              <input type="hidden" name="start_date" id="start_date" value="{{ $event->startDateFormatted()}}">
              <ul id="countdown"></ul>
              <?php
               use App\Http\Controllers\EventViewController as EventViewController;
              ?>
              <div class="calendarMenu">
                <div class="dropdown">
                  <button class="btn dropdown-toggle" type="button" id="calendarMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="ico-calendar"></i>&nbsp;&nbsp;ADD TO MY CALENDAR
                  </button>
                  <div class="dropdown-menu" aria-labelledby="calendarMenuButton">
                    <a class="dropdown-item" href="{{ EventViewController::getCalenderIcsAll("icalendar",$event->id) }}" target="_blank">iCalendar</a>
                    <a class="dropdown-item" href="{{ EventViewController::getCalenderIcsAll("google",$event->id) }}" target="_blank">Google Calendar</a>
                    <a class="dropdown-item" href="{{ EventViewController::getCalenderIcsAll("outlook",$event->id) }}" target="_blank">Outlook</a>
                    <a class="dropdown-item" href="{{ EventViewController::getCalenderIcsAll("weboutlook",$event->id) }}" target="_blank">Web Outlook</a>
                    <a class="dropdown-item" href="{{ EventViewController::getCalenderIcsAll("yahoo",$event->id) }}" target="_blank">Yahoo! Calendar</a>
                  </div>
                 </div>
              </div>
            </div>
        </div>

        <!-- @if ($event->images->count() && $event->event_image_position == 'right')
            <div class="col-md-5">
                <div class="content event_poster">
                    <img alt="{{$event->title}}" src="{{config('attendize.cdn_url_user_assets').'/'.$event->images->first()['image_path']}}" property="image">
                </div>
            </div>
        @endif
        @if ($event->images->count() && $event->event_image_position == 'after')
            <div class="col-md-12" style="margin-top: 20px">
                <div class="content event_poster">
                    <img alt="{{$event->title}}" src="{{config('attendize.cdn_url_user_assets').'/'.$event->images->first()['image_path']}}" property="image">
                </div>
            </div>
        @endif -->
    </div>
</section>

<script src="https://lobby.globalwomanclub.com/themes/globalwoman/javascript/moment/moment.js"></script>
<script src="https://lobby.globalwomanclub.com/themes/globalwoman/javascript/moment/moment-timezone-with-data.js"></script>
<script>
var start_date = document.getElementById('start_date').value;

// Set the date we're counting down to
// var countDownDate = new Date(start_date).getTime();
var countDownDate = moment.tz(start_date , "<?php echo $event->event_timezone ?>");
countDownDate.format(); // 2014-06-01T17:00:00+01:00

// Update the count down every 1 second
var x = setInterval(function() {

  // Get today's date and time
  // var now = new Date().getTime();
  var now = new Date();

  // Find the distance between now and the count down date
  var distance = countDownDate - now;

  // Time calculations for days, hours, minutes and seconds
  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
  var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

  // Output the result in an element with id="countdown"
  document.getElementById("countdown").innerHTML =
  "<li>" + "<span>" + days + "</span>" + "<p>" + "DAYS" + "</p>" + "</li>" +
  "<li>" + "<span>" + hours + "</span>" + "<p>" + "HOURS" + "</p>" + "</li>" +
  "<li>" + "<span>" + minutes + "</span>" + "<p>" + "MINUTES" + "</p>" + "</li>" +
  "<li>" + "<span>" + seconds + "</span>" + "<p>" + "SECONDS" + "</p>" + "</li>"

  // If the count down is over, write some text
  if (distance < 0) {
    clearInterval(x);
    document.getElementById("countdown").innerHTML = "EXPIRED";
  }
}, 1000);
</script>
