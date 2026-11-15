<section id="timezone" class="container">
  <div class="section_head">
    @if($event->start_date->diffInDays($event->end_date) >= 1)
    <h1 id="event_date">{{ $event->start_date->format('d') }} - {{ $event->end_date->format('d') }} {{ $event->start_date->format('F') }} {{ $event->start_date->format('Y') }}</h1>
    @else
    <h1 id="event_date">{{ $event->start_date->format('d') }} {{ $event->start_date->format('F') }} {{ $event->start_date->format('Y') }}</h1>
    @endif
    @if($event->start_date->diffInDays($event->end_date) >= 1)
    <p id="event_day">{{ $event->start_date->format('l') }} - {{ $event->end_date->format('l') }}</p>
    @else
    <p id="event_day">{{ $event->start_date->format('l') }}</p>
    @endif
  </div>

  <div class="row">
    <div class="col-md-2 col-sm-4 col-xs-6">
      <div class="timezone-box">
        <img src="https://www.globalwomansummit.com/hosted/images/38/18a8168d1d45cb9c93e4d961e944eb/flag.png" alt="">
        <h1>UTC - 4</h1>
        <h1>New York</h1>
        <h2>USA</h2>
        <?php
         $sDate = $event->start_date;
         $eDate = $event->end_date;
         $sDate->setTimezone(new DateTimeZone('America/New_York'));
         $eDate->setTimezone(new DateTimeZone('America/New_York'));
        ?>
        <h1 class="time"><b>{{ $sDate->format('H:i A') }}</b></h1>
        <h1 class="space"><b>-</b></h1>
        <h1 class="time"><b>{{ $eDate->format('H:i A') }}</b></h1>
      </div>
    </div>
    <div class="col-md-2 col-sm-4 col-xs-6">
      <div class="timezone-box">
        <img src="https://www.globalwomansummit.com/hosted/images/ab/37fe34ffde48ca892ffd4d21de9458/uk-2-.png" alt="">
        <h1>UTC + 1</h1>
        <h1>London</h1>
        <h2>England</h2>
        <?php
         $sDate->setTimezone(new DateTimeZone('Europe/London'));
         $eDate->setTimezone(new DateTimeZone('Europe/London'));
        ?>
        <h1 class="time"><b>{{ $sDate->format('H:i A') }}</b></h1>
        <h1 class="space"><b>-</b></h1>
        <h1 class="time"><b>{{ $eDate->format('H:i A') }}</b></h1>
      </div>
    </div>
    <div class="col-md-2 col-sm-4 col-xs-6">
      <div class="timezone-box">
        <img src="https://www.globalwomansummit.com/hosted/images/55/1e22e32bce47f1a27119a2eba0e155/flag-1-.png" alt="">
        <h1>UTC + 2</h1>
        <h1>Berlin</h1>
        <h2>Germany</h2>
        <?php
         $sDate->setTimezone(new DateTimeZone('Europe/Berlin'));
         $eDate->setTimezone(new DateTimeZone('Europe/Berlin'));
        ?>
        <h1 class="time"><b>{{ $sDate->format('H:i A') }}</b></h1>
        <h1 class="space"><b>-</b></h1>
        <h1 class="time"><b>{{ $eDate->format('H:i A') }}</b></h1>
      </div>
    </div>
    <div class="col-md-2 col-sm-4 col-xs-6">
      <div class="timezone-box">
        <img src="https://www.globalwomansummit.com/hosted/images/ab/3bd884bb33429b8d5ce670f70af2b0/malaysia.png" alt="">
        <h1>UTC + 8</h1>
        <h1>Kuala Lumpur</h1>
        <h2>Malaysia</h2>
        <?php
         $sDate->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
         $eDate->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
        ?>
        <h1 class="time"><b>{{ $sDate->format('H:i A') }}</b></h1>
        <h1 class="space"><b>-</b></h1>
        <h1 class="time"><b>{{ $eDate->format('H:i A') }}</b></h1>
      </div>
    </div>
    <div class="col-md-2 col-sm-4 col-xs-6">
      <div class="timezone-box">
        <img src="https://www.globalwomansummit.com/hosted/images/56/4856150cd54004abc1f81ebe028186/australia.png" alt="">
        <h1>UTC + 10</h1>
        <h1>Sydney</h1>
        <h2>Australia</h2>
        <?php
         $sDate->setTimezone(new DateTimeZone('Australia/Sydney'));
         $eDate->setTimezone(new DateTimeZone('Australia/Sydney'));
        ?>
        <h1 class="time"><b>{{ $sDate->format('H:i A') }}</b></h1>
        <h1 class="space"><b>-</b></h1>
        <h1 class="time"><b>{{ $eDate->format('H:i A') }}</b></h1>
      </div>
    </div>
    <div class="col-md-2 col-sm-4 col-xs-6">
      <div class="timezone-box">
        <img src="https://www.globalwomansummit.com/hosted/images/7b/954f224eaa4c90ab54ddf2400e4982/south-africa.png" alt="">
        <h1>UTC + 2</h1>
        <h1>Cape Town</h1>
        <h2>South Africa</h2>
        <?php
         $sDate->setTimezone(new DateTimeZone('Africa/Johannesburg'));
         $eDate->setTimezone(new DateTimeZone('Africa/Johannesburg'));
        ?>
        <h1 class="time"><b>{{ $sDate->format('H:i A') }}</b></h1>
        <h1 class="space"><b>-</b></h1>
        <h1 class="time"><b>{{ $eDate->format('H:i A') }}</b></h1>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
      <input type="hidden" name="start_date" id="start_date" value="{{ $event->startDateFormatted()}}">
      <h6 class="timezone-countdown-title text-center">YOUR TIME</h6>
      <?php
      $timezone_offset_minutes = 330;  // $_GET['timezone_offset_minutes']
      // Convert minutes to seconds
      $timezone_name = timezone_name_from_abbr("", $timezone_offset_minutes*60, false);
       $sDate->setTimezone(new DateTimeZone($timezone_name));
      ?>
      <p class="timezone-countdown-time text-center">{{ $sDate->format('H:i A') }} ({{$timezone_name}})</p>
      <ul id="timezone-countdown"></ul>
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

  // Output the result in an element with id="timezone-countdown"
  document.getElementById("timezone-countdown").innerHTML =
  "<li>" + "<span>" + days + "</span>" + "<p>" + "DAYS" + "</p>" + "</li>" +
  "<li>" + "<span>" + hours + "</span>" + "<p>" + "HOURS" + "</p>" + "</li>" +
  "<li>" + "<span>" + minutes + "</span>" + "<p>" + "MINUTES" + "</p>" + "</li>" +
  "<li>" + "<span>" + seconds + "</span>" + "<p>" + "SECONDS" + "</p>" + "</li>"

  // If the count down is over, write some text
  if (distance < 0) {
    clearInterval(x);
    document.getElementById("timezone-countdown").innerHTML = "EXPIRED";
  }
}, 1000);
</script>
