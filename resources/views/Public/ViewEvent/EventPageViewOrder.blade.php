@extends('Public.ViewEvent.Layouts.EventPage')

@section('content')

    @if($order->is_member)
        @include('Public.ViewEvent.Partials.EventHeaderSection')
    @else
        <section id="intro" class="container" style="margin-top:0;">
            <div class="hero-section" style="background-image:url()"></div>
        </section>
    @endif

    @include('Public.ViewEvent.Partials.EventViewOrderSection')
    @include('Public.ViewEvent.Partials.EventShareSection')
    @include('Public.ViewEvent.Partials.EventFooterSection')
    {{-- Show only up to june 26 2025 --}}
    @if(now()->isBefore('2025-06-27'))
        @include('Public.ViewEvent.Partials.SummitPopup')
    @endif
    
@stop