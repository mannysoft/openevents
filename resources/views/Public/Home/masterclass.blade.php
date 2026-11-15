<!DOCTYPE html>
<html lang="en">
<head>

  <title>Global Woman Events</title>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0" />

  <!-- bootstrap -->
  <link rel="stylesheet" href="assets/stylesheet/bootstrap.min.css" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700&display=swap" rel="stylesheet">

  <!-- fonts -->
  <link rel="stylesheet" href="assets/fonts/line-awesome/line-awesome-font-awesome.min.css" />

  <!-- datepicker -->
  <link rel="stylesheet" href="assets/javascript/fullcalendar/datepicker.css" />

  <!-- custom -->
  <link rel="stylesheet" href="assets/stylesheet/landing.css" />
  <link rel="stylesheet" href="assets/javascript/fullcalendar/fullcalendar.css" />

</head>

<body>

  @include('Public.Home.Partials.header')

  <section id="events">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">
          <div id="calendar"></div>

          <!-- calendar modal -->
          <div id="modal-view-event" class="modal modal-top fade calendar-modal">
          	<div class="modal-dialog modal-dialog-centered">
          		<div class="modal-content">
          			<div class="modal-body">
                  <img class="event-image" src="" alt="" style="width: 100%;margin-bottom: 1rem;">
          				<h4 class="modal-title"><span class="event-icon"></span><span class="event-title"></span></h4>
          				<div class="event-body"></div>
                  <a class="event-url" href="" target="_blank" style="color: #dc273f;font-style: italic;margin-top: 1rem;display: inline-block;">
                    Go To Masterclass
                  </a>
          			</div>
          			<div class="modal-footer">
          				<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
          			</div>
          		</div>
          	</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  @include('Public.Home.Partials.footer')

  <!-- jquery -->
  <script src="assets/javascript/jquery.min.js"></script>

  <!-- bootstrap -->
  <script src="assets/javascript/popper.min.js"></script>
  <script src="assets/javascript/bootstrap.min.js"></script>

  <!-- moment -->
  <script src="assets/javascript/fullcalendar/moment.js"></script>

  <!-- fullcalendar -->
  <script src="assets/javascript/fullcalendar/fullcalendar.min.js"></script>

  <!-- datepicker -->
  <script src="assets/javascript/fullcalendar/datepicker.js"></script>
  <script src="assets/javascript/fullcalendar/datepicker.en.js"></script>

  <script type="text/javascript">
   var passedArray = <?php echo json_encode($eventsData); ?>;
  jQuery(document).ready(function(){
    jQuery('.datetimepicker').datepicker({
        timepicker: true,
        language: 'en',
        range: true,
        multipleDates: true,
        multipleDatesSeparator: " - "
      });
  });

  (function () {
    'use strict';
    // ------------------------------------------------------- //
    // Calendar
    // ------------------------------------------------------ //
  jQuery(function() {
    // page is ready
    jQuery('#calendar').fullCalendar({
      themeSystem: 'bootstrap4',
      // emphasizes business hours
      businessHours: false,
      defaultView: 'month',
      // event dragging & resizing
      editable: true,
      // header
      header: {
        left: 'title',
        center: 'month,agendaWeek,agendaDay,list',
        right: 'today prev,next'
      },
      events: passedArray,
      eventRender: function(event, element) {
        if(event.icon){
          // element.find(".fc-title").prepend("<i class='fa fa-"+event.icon+"'></i>");
        }
        },
      // dayClick: function() {
      //   jQuery('#modal-view-event-add').modal();
      // },
      eventClick: function(event, jsEvent, view) {
          // jQuery('.event-icon').html("<i class='fa fa-"+event.icon+"'></i>");
          jQuery('.event-image').attr('src',event.image);
          jQuery('.event-title').html(event.title);
          jQuery('.event-body').html(event.description);
          jQuery('.event-url').attr('href',event.eventurl);
          jQuery('#modal-view-event').modal();
      },
    })
  });

  })(jQuery);
  </script>
</body>
</html>
