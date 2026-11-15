@extends('Public.ViewEvent.Layouts.EventPage')

@section('head')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
@stop

@section('content')
@include('Public.ViewEvent.Partials.EventHeaderSection')
@include('Public.ViewEvent.Partials.EventPaymentSection')
@include('Public.ViewEvent.Partials.EventFooterSection')
@stop

