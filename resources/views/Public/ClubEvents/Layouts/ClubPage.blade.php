<!DOCTYPE html>
<html lang="en">
@use('GrahamCampbell\Markdown\Facades\Markdown')
<head>
    @php
    if($club== "London-mayfair"){
    $club1 = "London Mayfair";}
    else{
    $club1 = $club;
    }

    $clubName = $club1;
    if ($clubName == 'Accra') {
    $clubName = 'Global Woman Club Online';
    }

    // An event with no club has no club name, so the page goes by the event.
    if ($clubName == '') {
    $clubName = $event->title;
    }
    @endphp
    <title>{{ $clubName }} - Global Woman Events</title>


    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0" />
    <link rel="canonical" href="{{url()->current()}}" />


    <!-- Open Graph data -->
    <meta property="og:title" content="{{ $clubName }} - Global Woman Events" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{{url()->current()}}" />
    @php
    $eventImage = App\Models\EventImage::where('event_id', '=', $event->id)->get();
    @endphp
    @foreach($eventImage as $img)
    <meta property="og:image" content="https://davxegdvkjbks.cloudfront.net/{{$img->image_path}}" />
    @endforeach
    <meta property="og:description" content="{{Str::words(strip_tags(Markdown::convertToHtml($event->description_format))), 20}}" />
    <meta property="og:site_name" content="{{ config('app.url') }}" />

    <link rel="stylesheet" href="{{ request()->root() }}/assets/stylesheet/bootstrap.min.css" />
    <link rel="stylesheet" href="{{ request()->root() }}/assets/stylesheet/event.css?v=1" />
    <link rel="stylesheet" href="{{ request()->root() }}/assets/stylesheet/globalman.css" />

    <!-- intlTelInput -->
    <link rel="stylesheet" href="{{ request()->root() }}/assets/stylesheet/intlTelInput.min.css">

    <!-- fonts -->
    <link rel="stylesheet" href="{{ request()->root() }}/assets/fonts/fontawesome/all.css" />

    <link rel="stylesheet" href="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/gw-header/t-header.css" />
    @if(isset($globalmanclub) && $globalmanclub == true)
    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" />
    @endif
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
    @yield('head')

</head>

<body class="attendize">

    <div id="event_page_wrap" vocab="http://schema.org/" typeof="Event">
        <div id="gw-header"></div>
        @yield('content')

        {{-- Push for sticky footer--}}
        @stack('footer')
    </div>

    {{-- Sticky Footer--}}
    @yield('footer')

    <script src="{{ config('app.url') }}/assets/javascript/jquery.min.js"></script>
    <script src="{{ config('app.url') }}/assets/javascript/popper.min.js"></script>
    <script src="{{ config('app.url') }}/assets/javascript/bootstrap.min.js"></script>
    <script src="{{ config('app.url') }}/assets/javascript/intlTelInput.min.js"></script>
    <script src="{{ config('app.url') }}/assets/javascript/intlTelInput-jquery.min.js"></script>
    <script src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/gw-header/t-header.js"></script>
    @if(isset($globalmanclub) && $globalmanclub == true)
    <script src="https://unpkg.com/grapesjs"></script>
    @include('Public.ClubEvents.Layouts.GrapeJs')
    @endif
    <script type="text/javascript">
        $('.carousel').carousel({
            interval: 5000
        })
    </script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-5T8FWW4Z1P"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-5T8FWW4Z1P');
    </script>

    @include('Public.ClubEvents.fbpixels', ['view_event' => true])
</body>

</html>