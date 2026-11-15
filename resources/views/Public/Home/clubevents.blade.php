<!DOCTYPE html>
<html lang="en">

<head>

    <title>Global Woman Events</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0" />

    <!-- bootstrap -->
    <link rel="stylesheet" href="{{ config('app.url') }}/assets/stylesheet/bootstrap.min.css" />

    <!-- fonts -->
    <link rel="stylesheet" href="{{ config('app.url') }}/assets/fonts/fontawesome/all.css" />

    <link rel="stylesheet" href="{{ request()->root() }}/assets/stylesheet/footer.css" />

    <!-- custom -->
    <link rel="stylesheet" href="{{ config('app.url') }}/assets/stylesheet/landing.css" />

</head>

<body>

    @include('Public.Home.Partials.header')

    <section id="club-meeting" style="padding-bottom: 10px;">
        <div class="container-fluid">
            <div class="section-title text-center">
                @if($club == "nottinghill")
                <h2><span>Discover the Global Woman Club Notting Hill</span></h2>
                @elseif($club == "london-mayfair")
                <h2><span>Discover the Global Woman Club London Mayfair</span></h2>
                @elseif($club == "accra" || $club == "online")
                <h2><span>Discover the Global Woman Club Online</span></h2>
                @else
                <h2><span>Discover the Global Woman Club {{ ucfirst($club) }}</span></h2>
                @endif
            </div>

            <div class="row">
                @if(count($eventsData) > 0)
                @foreach($eventsData as $event)
                @php
                $newDate = date("D, d M Y", strtotime($event['start_date']));
                @endphp
                <div class="col-md-3">
                    <div class="grid-widget" style="height: 340px;">
                        <div class="widget-header">
                            <a href="{{$event['eventUrl']}}" target="_blank" class="opacity">
                                <img width="370" height="150" src="{{ $event['postImage'] }}" alt="">
                            </a>

                        </div>
                        <div class="widget-body">
                            <h3>{{$event['postTitle']}}</h3>
                            <p style="color:#818181;">{{$event['eventType']}}</p>
                            <p class="date">
                                <span class="float-left"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{$newDate}}</span>
                                <span class="float-right"><i class="fa fa-clock" aria-hidden="true"></i> {{$event['start_time']}}</span>
                            </p>

                            <div>
                                <a style="background-color: #dc273f;padding: 8px 20px;border-radius: 2px;text-align: center;" href="{{$event['eventUrl'] }}" class="btn bg-red text-white">Book your ticket now</a>
                            </div>


                            {{-- <div style="width:100%;display:inline-block;border-top: 1px solid #ddd;margin:16px 0 0;">
                                <div class="float-right">

                                </div>
                            </div> --}}
                        </div>
                    </div>
                </div>
                @endforeach
                @else
                <h1 style="text-align: center;width: 100%">No Upcoming Events Found</h1>
                @endif

            </div>
        </div>
    </section>

    @include('Public.Home.Partials.footer')

    <!-- jquery -->
    <script src="{{ config('app.url') }}/assets/javascript/jquery.min.js"></script>

    <!-- bootstrap -->
    <script src="{{ config('app.url') }}/assets/javascript/popper.min.js"></script>
    <script src="{{ config('app.url') }}/assets/javascript/bootstrap.min.js"></script>

    <script>
        filterSelection("all")

        function filterSelection(c) {
            if (c != "all") {
                var element = document.getElementById("all");
                element.classList.remove("all");
            }
            var x, i;
            x = document.getElementsByClassName("column");
            if (c == "all") c = "";
            for (i = 0; i < x.length; i++) {
                w3RemoveClass(x[i], "show");
                if (x[i].className.indexOf(c) > -1) w3AddClass(x[i], "show");
            }
        }

        function w3AddClass(element, name) {
            var i, arr1, arr2;
            arr1 = element.className.split(" ");
            arr2 = name.split(" ");
            for (i = 0; i < arr2.length; i++) {
                if (arr1.indexOf(arr2[i]) == -1) {
                    element.className += " " + arr2[i];
                }
            }
        }

        function w3RemoveClass(element, name) {
            var i, arr1, arr2;
            arr1 = element.className.split(" ");
            arr2 = name.split(" ");
            for (i = 0; i < arr2.length; i++) {
                while (arr1.indexOf(arr2[i]) > -1) {
                    arr1.splice(arr1.indexOf(arr2[i]), 1);
                }
            }
            element.className = arr1.join(" ");
        }


        // Add active class to the current button (highlight it)
        var btnContainer = document.getElementById("myBtnContainer");
        var btns = btnContainer.getElementsByClassName("btn");
        for (var i = 0; i < btns.length; i++) {
            btns[i].addEventListener("click", function() {
                var current = document.getElementsByClassName("active");
                current[0].className = current[0].className.replace(" active", "");
                this.className += " active";
            });
        }
    </script>
</body>

</html>