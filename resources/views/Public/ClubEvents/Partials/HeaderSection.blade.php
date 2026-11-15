<nav class="navbar navbar-expand-lg navbar-light @if(isset($bgColorClass)) {{ $bgColorClass }} @else bg-red @endif">
    <div class="container">
        <a class="navbar-brand" href="{{ config('app.url') }}">
            @if(isset($globalmanclub) && $globalmanclub == true)
            <img src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/hbgnew1.png" alt="Global Man Logo">
            @else
            <img src="{{ config('app.url') }}/assets/images/logo-white.png" alt="Global Woman Logo">
            @endif
        </a>
        <ul class="navbar-nav m-auto">
            <li class="nav-item">
                {{-- <span class="{{ @$textColorClass }}">{{ $event->title ?? @$event->postTitle }}</span> --}}
                
                @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null)

                @if(request('edit_event') == 1)
                <a style="color: white;" href="{{ request()->root() }}/club/{{ $event->id }}">
                    <- Exit Edit
                </a>
                @else
                <a style="color: white;" href="{{ request()->root() }}/club/{{ $event->id }}?edit_event=1">
                    Edit Content ->
                </a>
                @endif
                @endif
            </li>
        </ul>
        @if(isset($memberLogo))
        <img height="80" src="https://d1csarkz8obe9u.cloudfront.net/posterpreviews/testing-logo-design-template-ce84480d61b3db9a8e1522a99875832f_screen.jpg" alt="Member logo">
        <br>
        <span style="color:white;font-size:xx-small">Global Woman is a partner of Event organizer..</span>
        @endif
    </div>
</nav>
{{--
@include('Public.Partials.banner')
--}}