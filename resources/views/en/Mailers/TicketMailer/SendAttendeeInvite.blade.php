@extends('en.Emails.Layouts.Master')

@section('message_content')
Hello {{$attendee->first_name}},<br><br>

You have been invited to the event  <b>{{$attendee->order->event->title}}</b>.<br/>
@if ($ticketAttached ?? true)
Your ticket for the event is attached to this email.
@else
Your place is confirmed: you are on the attendee list for this event.<br/>
Your order reference is <b>{{ $orderReference }}</b>. If you are asked for your ticket at the event, give your name or this reference.<br/>
You can view your booking here: <a href="{{ $orderUrl }}">{{ $orderUrl }}</a>
@endif

<br><br>
Regards
@stop
