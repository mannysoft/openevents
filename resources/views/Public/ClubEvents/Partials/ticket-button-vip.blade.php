<div class="row justify-content-center">

    @if(isset($hybridVip))
        @include('Public.ClubEvents.Partials.ticket', ['vipTicket' => true])

    @else
        @include('Public.ClubEvents.Partials.ticket', ['vipTicket' => true])
        @include('Public.ClubEvents.Partials.ticket', ['vipTicket' => false])
    @endif

</div>