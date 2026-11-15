<section id="video-section" class="section-ticket-book">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-sm-12 align-self-start">
                @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
                <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="event_video" data-has-video="true" data-video-only="true" data-club-id="{{ $clubData->id }}" data-content="{{ @$clubData->event_video }}" data-label="Event Video URL (You can paste YouTube video here)">
                    Edit Video
                </a>
                @endif
                @if ($eventsVideo != '')
                <div class="embed-responsive embed-responsive-16by9">
                    @if($event->id=="1" || $event->id=="2" || $event->id=="3")
                    <video id="video-wrapper" class="embed-responsive-item">
                        <source src="https://www.youtube.com/watch?v=5GbqrNidpzE">
                    </video>
                    @else
                    <!-- Club video from clubs table in events db -->
                    {{-- allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" --}}
                    <iframe width="560" height="315" src="{{$eventsVideo}}" title="Global Woman Club video player" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    @endif
                </div>
                @endif

                <div class="video-content">
                    
                    @if($club=='Losangeles')
                    <h1 class="m-0">Los Angeles {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='Orange-County')
                    <h1 class="m-0">Orange County, California {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='Newyork')
                    <h1 class="m-0">New York {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='Nottinghill')
                    <h1 class="m-0">Notting Hill {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='Metrodc')
                    <h1 class="m-0">Metro DC {{ $event->start_date->format('Y') }}</h1>
                    @elseif($club=='London-mayfair')
                    <p class="m-0" style="font-family:'Vidaloka';font-size:21px;">{{$event->title}}</p> <br>
                    @else
                    @if($club == 'Accra')
                    <h1 class="m-0">Global Woman Club Online {{ $event->start_date->format('Y') }}</h1>
                    @else
                        @if (! is_string($event->start_date))
                        <h1 class="m-0">{{$club}} {{ $event->start_date->format('Y') }}</h1>
                        @endif
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>