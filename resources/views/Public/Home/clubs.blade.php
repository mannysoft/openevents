<!DOCTYPE html>
<html lang="en">

<head>

    <title>Global Woman Events</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0" />

    <!-- bootstrap -->
    <link rel="stylesheet" href="assets/stylesheet/bootstrap.min.css" />

    <!-- fonts -->
    <link rel="stylesheet" href="assets/fonts/fontawesome/all.css" />

    <!-- custom -->
    <link rel="stylesheet" href="assets/stylesheet/landing.css" />

</head>

<body>

    @include('Public.Home.Partials.header')

    <section id="clubs">
        <div class="container-fluid">
            <div class="section-title text-center">
                <h2><span>View all published Global Woman Club events</span></h2>
                <p>Click/tap on any club banner to see upcoming & future events to book</p>
            </div>
            <div id="myBtnContainer">
                <button class="btn all" id="all" onclick="filterSelection('all')"> All</button>
                <button class="btn" onclick="filterSelection('albania')"> Albania</button>
                <button class="btn" onclick="filterSelection('canada')"> Canada</button>

                <button class="btn" onclick="filterSelection('cyprus')"> Cyprus</button>
                <button class="btn" onclick="filterSelection('france')"> France</button>
                <button class="btn" onclick="filterSelection('ghana')">Ghana</button>
                <button class="btn" onclick="filterSelection('germany')"> Germany</button>

                <button class="btn" onclick="filterSelection('mongolia')">Mongolia</button>
                <button class="btn" onclick="filterSelection('netherlands')"> Netherlands</button>
                <button class="btn" onclick="filterSelection('philippines')"> Philippines</button>
                <!--      <button class="btn" onclick="filterSelection('belgium')"> Belgium</button>
   <button class="btn" onclick="filterSelection('norway')"> Norway</button> 
   <button class="btn" onclick="filterSelection('romania')"> Romania</button>
   <button class="btn" onclick="filterSelection('kosova')">Kosova</button> 
    
   <button class="btn" onclick="filterSelection('south-africa')"> South Africa</button> 
   <button class="btn" onclick="filterSelection('austria')"> Austria</button>-->
                <button class="btn" onclick="filterSelection('qatar')"> Qatar</button>
                <button class="btn" onclick="filterSelection('singapore')"> Singapore</button>

                <button class="btn" onclick="filterSelection('sweden')"> Sweden</button>
                <button class="btn" onclick="filterSelection('switzerland')">Switzerland</button>
                <button class="btn" onclick="filterSelection('turkey')">Turkey</button>
                <button class="btn" onclick="filterSelection('dubai')"> UAE</button>
                <button class="btn" onclick="filterSelection('uk')"> UK</button>
                <button class="btn" onclick="filterSelection('usa')"> USA</button>

            </div>

            <div class="row">
                
                @foreach ($clubs as $club)
                    <div class="column {{ $club->event_column }}">
                        <a href="{{ config('app.url') }}/clubs/{{ $club->slug }}">
                            <div class="img">
                                <img src="{{ $club->image }}" alt="">
                            </div>
                            <h4>{{ $club->title }}</h4>
                        </a>

                    </div>
                @endforeach

            </div>
        </div>
    </section>


    @include('Public.Home.Partials.footer')

    <!-- jquery -->
    <script src="assets/javascript/jquery.min.js"></script>

    <!-- bootstrap -->
    <script src="assets/javascript/popper.min.js"></script>
    <script src="assets/javascript/bootstrap.min.js"></script>

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