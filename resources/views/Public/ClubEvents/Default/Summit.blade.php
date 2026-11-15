@foreach($eventsData as $event)
@extends('Public.ClubEvents.Layouts.ClubPage')
@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SummitSectionOne')
@include('Public.ClubEvents.Partials.TicketBookSection')
{{-- @include('Public.ClubEvents.Partials.SummitDefaultSection') --}}
@include('Public.ClubEvents.Partials.SummitFooterSection')
@endsection
@endforeach