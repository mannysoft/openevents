<link rel="stylesheet" href="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/gw-header/t-header.css">
<link rel="stylesheet" href="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/gw-header/magazine.css">
<link rel="stylesheet" href="{{ request()->root() }}/assets/stylesheet/footer.css" />
<div id="gw-header"></div>
<!-- navbar -->
<nav class="navbar navbar-expand-lg fixed-top navbar-dark bg-dark" style="margin-top: 46px">
    <div class="container-fluid">
        <a class="navbar-brand" href="javascript:void(0);">
            <img src="assets/images/logo-white.png" alt="">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav m-auto">
                <li class="nav-item" id="h">
                    <a class="nav-link" href="{{ config('app.url') }}">Home</a>
                </li>
                <li class="nav-item" id="c">
                    <a class="nav-link" href="{{ config('app.url') }}/clubs">Clubs</a>
                </li>
                <li class="nav-item" id="e">
                    <a class="nav-link" href="{{ config('app.url') }}/events">All Events</a>
                </li>
                <li class="nav-item" id="m">
                    <a class="nav-link" href="{{ config('app.url') }}/masterclass">All Masterclass</a>
                </li>
            </ul>
            @if($loginData==0)
            <form class="form-inline ml-3">
                <a href="{{ config('app.url') }}/login" class="text-white">Sign In</a>
            </form>
            @endif
        </div>

        <!-- user-profile -->
        @if($loginData==1)
        <div class="user-profile">
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="{{$avatar}}" /> {{$firstname}} {{$lastname}}
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    @if($admin==1)
                    <a class="dropdown-item" href="{{ config('app.url') }}/organiser/11/dashboard" target="_blank"><i class="fa fa-th-large" aria-hidden="true"></i> Dashboard</a>
                    @endif
                    <a class="dropdown-item" href="{{ config('app.url') }}/login" target='_blank'><i class="fa fa-users" aria-hidden="true"></i> Members Lounge</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ config('app.url') }}/logout"><i class="fa fa-lock" aria-hidden="true"></i> Log Out</a>
                </div>
            </div>
        </div>
        @endif
    </div>
</nav>

<!-- jquery -->
<script src="assets/javascript/jquery.min.js"></script>
<script type="text/javascript">
    var x = window.location.pathname;
    console.log(x);
    $(document).ready(function() {
        if (x == "/clubs") {
            $('#c').addClass('enabled');
        } else if (x == "/events") {
            $('#e').addClass('enabled');
        } else if (x == "/masterclass") {
            $('#m').addClass('enabled');
        } else {
            $('#h').addClass('enabled');
        }
    });
</script>
<script src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/gw-header/t-header.js"></script>