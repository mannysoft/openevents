@if(!$event->is_live)
<section id="goLiveBar">
    <div class="container">
        @if(!$event->is_live)

        {{ @trans("ManageEvent.event_not_live") }}
        <a href="{{ route('MakeEventLive' , ['event_id' => $event->id]) }}"
           style="background-color: green; border-color: green;"
        class="btn btn-success btn-xs">{{ @trans("ManageEvent.publish_it") }}</a>
        @endif
    </div>
</section>
@endif
<!-- <section id="organiserHead" class="container-fluid">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div onclick="window.location='{{$event->event_url}}#organiser'" class="event_organizer">
                    <b>{{$event->organiser->name}}</b> @lang("Public_ViewEvent.presents")
                </div>
            </div>
        </div>
    </div>
</section> -->
<section id="intro" class="container" style="margin-top:0;">
          <!-- <div class="intro-header">
           <ul class="intro-group">
                <li class="intro-group-item">
                    <a class="btn-event-link" href="{{{$event->event_url}}}#tickets">@lang("Public_ViewEvent.TICKETS")</a>
                </li>
                <li class="intro-group-item">
                    <a class="btn-event-link" href="{{{$event->event_url}}}#details">@lang("Public_ViewEvent.DETAILS")</a>
                </li>
                <li class="intro-group-item">
                    <a class="btn-event-link" href="{{{$event->event_url}}}#location">@lang("Public_ViewEvent.LOCATION")</a>
                </li>
            </ul>
          </div> -->

          @php
            $imagePath = '';
            if ($event->images->first() != null) {
                $imagePath = $event->images->first()['image_path'];
            }
          @endphp

          <div class="hero-section" style="background-image:url({{ 'https://davxegdvkjbks.cloudfront.net/'.$imagePath}})">
            <h1 class="e-title" property="name">{{$event->title}}</h1>
            <div class="event_venue">

                <div class="date-grid">
                  <div class="column">
                    <span class="date">{{ $event->start_date->format('d') }}</span>
                    <p class="month">{{ $event->start_date->format('M') }}</p>
                    <p class="year">{{ $event->start_date->format('Y') }}</p>
                  </div>
                  <div class="time">
                    <i class="ico-clock"></i><br />
                    <span>{{ $event->start_date->format('H:i') }}</span>
                  </div>
                  @if($event->start_date->diffInDays($event->end_date) >= 1)
                  &nbsp;<span style="padding-left:15px;">to</span>&nbsp;
                  <div class="column">
                    <span class="date">{{ $event->end_date->format('d') }}</span>
                    <p class="month">{{ $event->end_date->format('M') }}</p>
                    <p class="year">{{ $event->end_date->format('Y') }}</p>
                  </div>
                  <div class="time">
                    <i class="ico-clock"></i><br />
                    <span>{{ $event->end_date->format('H:i') }} </span>
                  </div>
                  @else
                  &nbsp;<span style="padding:0 15px;">to</span>&nbsp;
                  <div class="time">
                    <i class="ico-clock"></i><br />
                    <span>{{ $event->end_date->format('H:i') }}</span>
                  </div>
                  @endif
                </div>

                <!-- <p class="e-when">
                  <span property="startDate" content="{{ $event->start_date->toIso8601String() }}">
                      {{ $event->startDateFormatted() }}
                  </span>
                  -
                  <span property="endDate" content="{{ $event->end_date->toIso8601String() }}">
                       @if($event->start_date->diffInDays($event->end_date) == 0)
                          {{ $event->end_date->format('H:i') }}
                       @else
                          {{ $event->endDateFormatted() }}
                       @endif
                  </span>
                </p> -->
                <p class="e-where">
                 <!-- @lang("Public_ViewEvent.at")
                <span property="location" typeof="Place">
                    <b property="name">{{$event->venue_name}}</b>
                    <meta property="address" content="{{ urldecode($event->venue_name) }}">
                </span> -->
              </p>
            </div>
          </div>

</section>
