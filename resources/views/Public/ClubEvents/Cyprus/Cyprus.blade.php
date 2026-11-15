@foreach($eventsData as $event)
@extends('Public.ClubEvents.Layouts.ClubPage')

@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionOne')
@include('Public.ClubEvents.Partials.TicketBookSection')
@include('Public.ClubEvents.Partials.QuoteSection')

@include('Public.ClubEvents.Partials.DefaultSectionTwo')
@include('Public.ClubEvents.Partials.LobbySection')
@include('Public.ClubEvents.Partials.QuoteSectionTwo')
@include('Public.ClubEvents.Default.Partials.AboutSection')
{{-- @include('Public.ClubEvents.Cyprus.Partials.MirelaSection') --}}
@include('Public.ClubEvents.Default.Partials.MeetingSection')
@include('Public.ClubEvents.Partials.BookCallSection', ['bookCallimage' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/call.jpeg'])
@include('Public.ClubEvents.Default.Partials.HostInfoSection')
{{--
    @include('Public.ClubEvents.Cyprus.Partials.HostInfoSection')
--}}
@include('Public.ClubEvents.Default.Partials.ClubInfoSection')
@include('Public.ClubEvents.Default.Partials.ClubGallerySection', ['clubGalleryImages' => [
    'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/1.jpeg',
    'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/2.jpeg',
    'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/3.jpeg',
    'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/0.jpeg',
]
])
@include('Public.ClubEvents.Default.Partials.GalleryCardSection')
@include('Public.ClubEvents.Partials.DefaultBenefitsSection')
{{-- @include('Public.ClubEvents.Cyprus.Partials.BannerSection') --}}
@include('Public.ClubEvents.Default.Partials.BannerSection-1')
@include('Public.ClubEvents.Default.Partials.TestimonialsSection')
@include('Public.ClubEvents.Partials.SectionThree')
@include('Public.ClubEvents.Partials.MemberSection')
@include('Public.ClubEvents.Default.Partials.GallerySection', ['galleryImages' => [
    'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/5.jpeg',
    'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/6.jpeg',
    'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/7.jpeg',
    //'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/8.jpeg',
]
])
@include('Public.ClubEvents.Partials.FooterSection')
@endsection

@endforeach