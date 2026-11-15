@extends('Public.ClubEvents.Layouts.MemberPage')

@section('content')
{{--
@include('Public.ClubEvents.Partials.HeaderSection', ['memberLogo' => '', 'hideBanner' => true])
--}}
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionOne')
@include('Public.ClubEvents.Partials.TicketBookSection', ['hideCoupon' => true, 'removeVideo' => false])
{{--
@include('Public.ClubEvents.Partials.QuoteSection')
--}}
@include('Public.ClubEvents.Partials.CountdownSection')
{{--
@include('Public.ClubEvents.Partials.DefaultSectionTwo')
--}}
@include('Public.ClubEvents.Partials.MemberSectionTwo')
@include('Public.MemberEvents.Member.Partials.YouCanSpeak')
{{--
@include('Public.MemberEvents.Member.Partials.TestimonialsSection')
--}}
{{--
@include('Public.ClubEvents.Partials.FooterSection', ['hideFooter' => true])
--}}
@endsection