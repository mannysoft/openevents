<section id="section-countdown" class="section-countdown">
  <div class="container">
    <p class="countdown-title text-center">Event Will Start In</p>
    <ul id="countdown"></ul>
    <?php
     use App\Http\Controllers\EventViewController as EventViewController;
    ?>
    <div class="calendarMenu">
      <div class="dropdown">
        <button class="btn dropdown-toggle" type="button" id="calendarMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="far fa-calendar-plus"></i>&nbsp;&nbsp;Add To My Calendar
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
</section>



<script src="https://lobby.globalwomanclub.com/themes/wondertag/javascript/moment/moment.js"></script>
<script src="https://lobby.globalwomanclub.com/themes/wondertag/javascript/moment/moment-timezone-with-data.js"></script>
<script>
var start_date = "<?php echo $event->start_date ?>";
var timzone = "<?php echo $event->event_timezone ?>";

// Set the date we're counting down to
var countDownDate = moment.tz(start_date , timzone);
countDownDate.format(); // 2014-06-01T17:00:00+01:00

// Update the count down every 1 second
var x = setInterval(function() {

  // Get today's date and time
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
  "<li>" + "<span>" + days + "</span>" + "<p>" + "Days" + "</p>" + "</li>" +
  "<li>" + "<span>" + hours + "</span>" + "<p>" + "Hours" + "</p>" + "</li>" +
  "<li>" + "<span>" + minutes + "</span>" + "<p>" + "Minutes" + "</p>" + "</li>" +
  "<li>" + "<span>" + seconds + "</span>" + "<p>" + "Seconds" + "</p>" + "</li>"

  // If the count down is over, write some text
  if (distance < 0) {
    clearInterval(x);
    document.getElementById("section-countdown").style.display = "none";
    document.getElementById("section-live").style.display = "block";
    document.getElementById("countdown").innerHTML = "EXPIRED";
  }
}, 1000);
</script>
