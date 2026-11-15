<!DOCTYPE html>
<html lang="en">
@use('GrahamCampbell\Markdown\Facades\Markdown')
<head>
    <title>Global Woman Cub Member Events</title>


    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0" />
    <link rel="canonical" href="{{url()->current()}}" />


    <!-- Open Graph data -->
    <meta property="og:title" content="Global Woman Cub Member Events" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{{url()->current()}}" />
    <meta property="og:image" content="{{ $event->postImage }}" />
    <meta property="og:description" content="{{Str::words(strip_tags(Markdown::convertToHtml($event->postTitle ))), 20}}" />
    <meta property="og:site_name" content="{{ config('app.url') }}" />

    <link rel="stylesheet" href="{{ config('app.url') }}/assets/stylesheet/bootstrap.min.css" />
    <link rel="stylesheet" href="{{ config('app.url') }}/assets/stylesheet/event.css?v=1" />

    <!-- intlTelInput -->
    <link rel="stylesheet" href="{{ config('app.url') }}/assets/stylesheet/intlTelInput.min.css">

    <!-- fonts -->
    <link rel="stylesheet" href="{{ config('app.url') }}/assets/fonts/fontawesome/all.css" />

    <link rel="stylesheet" href="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/gw-header/t-header.css" />
    @yield('head')

</head>

<body class="attendize">
    <script type="text/javascript" src="https://secure.rate8deny.com/js/218880.js"></script>
    <noscript><img alt="" src="https://secure.rate8deny.com/218880.png" style="display:none;" /></noscript>

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


</body>

</html>