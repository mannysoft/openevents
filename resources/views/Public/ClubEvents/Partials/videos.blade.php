<!-- Public.ClubEvents.Partials.videos -->
@if(isset($videos))
<section id="section-gallery">
    <div class="container">
        
        @foreach ($videos as $video)
            @if($loop->odd)
            <div class="row">
            @endif

            @if($video['type'] == 's3')
            <!-- <div class="col-lg-6 col-sm-6"> -->
            <div class="col-lg-6 col-sm-12 align-self-center">
                
                <video id="video-wrapper" width="100%" height="100%" class="embed-responsive-item" poster="{{ @$video['poster'] }}" controls>
                    <source src="{{ $video['video'] }}">
                </video>
                
                <!-- <div id="video-wrapper" width="100%" height="100%" class="embed-responsive-item"> -->
                <!-- <iframe width="100%" height="300" src="{{ $video['video'] }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe> -->
                <!-- </div> -->
            </div>
            @else
            <div class="col-lg-6 col-sm-12 align-self-center mb-4">
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe width="560" height="315" src="{{ $video['video'] }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>
            @endif

            @if($loop->even)
            </div>
            @endif
        @endforeach


           <!-- <div class="row">
            <div class="col-lg-6 col-sm-6">
                <video id="video-wrapper" width="100%" height="100%" class="embed-responsive-item" poster="https://i.ytimg.com/vi/9c5lGe-RuRA/maxresdefault.jpg?sqp=-oaymwEmCIAKENAF8quKqQMa8AEB-AH-CYAC0AWKAgwIABABGGUgWChPMA8=&rs=AOn4CLDSzYEelWeLJ6M3oueOjAa1F-7wxg" controls>
                    <source src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/The+Power+of+the+Butterfly+Effect+-+Dr+Ervin+Laszlo+visits+GWC+in+London.mp4">
                </video>
            </div>
            <div class="col-lg-6 col-sm-6">
                <video id="video-wrapper" width="100%" height="100%" class="embed-responsive-item" poster="https://i.ytimg.com/vi/vfcyqTgysSg/maxresdefault.jpg?sqp=-oaymwEmCIAKENAF8quKqQMa8AEB-AH-CYAC0AWKAgwIABABGGUgWChMMA8=&rs=AOn4CLB91wRxLXOcxQkc0qwL9IGrqn6OnQ" controls>
                    <source src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/Global+Woman+Club+in+Dubai.mp4">
                </video>
            </div>
        </div> -->

        <!-- <div class="row">
            <div class="col-lg-6 col-sm-6">
                <iframe style="object-fit: contain;overflow-clip-margin: content-box;overflow: clip;box-sizing: border-box" width="100%" height="100%" src="https://www.youtube.com/embed/S1W0SC7KV9c?si=W-BGE6PeaVA1mdbz&amp;controls=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
            <div class="col-lg-6 col-sm-6">
            <video id="video-wrapper" width="100%" height="100%">
                    
                </video>
            </div>
        </div> -->

        <!-- <div class="row">
            <div class="col-lg-6 col-sm-6">
                <video id="video-wrapper" width="100%" height="100%" class="embed-responsive-item" poster="https://i.ytimg.com/vi/9c5lGe-RuRA/maxresdefault.jpg?sqp=-oaymwEmCIAKENAF8quKqQMa8AEB-AH-CYAC0AWKAgwIABABGGUgWChPMA8=&rs=AOn4CLDSzYEelWeLJ6M3oueOjAa1F-7wxg" controls>
                    <source src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/The+Power+of+the+Butterfly+Effect+-+Dr+Ervin+Laszlo+visits+GWC+in+London.mp4">
                </video>

            </div>
            <div class="col-lg-6 col-sm-6">
                <video id="video-wrapper" width="100%" height="100%" class="embed-responsive-item" poster="https://i.ytimg.com/vi/vfcyqTgysSg/maxresdefault.jpg?sqp=-oaymwEmCIAKENAF8quKqQMa8AEB-AH-CYAC0AWKAgwIABABGGUgWChMMA8=&rs=AOn4CLB91wRxLXOcxQkc0qwL9IGrqn6OnQ" controls>
                    <source src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/Global+Woman+Club+in+Dubai.mp4">
                </video>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-sm-6">
                <video id="video-wrapper" width="100%" height="100%" class="embed-responsive-item" poster="https://i.ytimg.com/vi/7ut4nTrvVKw/maxresdefault.jpg?sqp=-oaymwEmCIAKENAF8quKqQMa8AEB-AH-CYAC0AWKAgwIABABGGUgWChTMA8=&rs=AOn4CLAQ8zOcOh2mBLqSw0rKXob_6n8ZXQ" controls>
                    <source src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/Global+Woman+Club+in+London+-+February+event.mp4">
                </video>
            </div>

            <div class="col-lg-6 col-sm-6">
                <video id="video-wrapper" width="100%" height="100%" class="embed-responsive-item" poster="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/02A.png" controls>
                    <source src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/GWC+testimonial+1.mp4">
                </video>
            </div>
        </div> -->
    </div>
</section>
@endif