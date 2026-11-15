@foreach($eventsData as $event)
@extends('Public.ClubEvents.Layouts.ClubPage')

@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionOne')
{{-- @include('Public.ClubEvents.Partials.VideoSection') --}}
@include('Public.ClubEvents.Partials.TicketBookSection')
@include('Public.ClubEvents.Partials.DefaultSectionTwo')
@include('Public.ClubEvents.Default.Partials.About1Section')
@if(isset($globalmanclub) && $globalmanclub == true)
{{--
@include('Public.ClubEvents.Partials.Edit')
--}}
@endif
{{-- Show countdown on globalwoman only --}}
@if(isset($globalmanclub) && $globalmanclub == true)
@else
@include('Public.ClubEvents.Partials.CountdownSection')
@endif
{{--  --}}
<!-- If global man club -->
@if(isset($globalmanclub) && $globalmanclub == true)
@include('Public.ClubEvents.Default.Partials.GlobalManSections')
@else
@include('Public.ClubEvents.Partials.QuoteSectionTwo')
@include('Public.ClubEvents.Default.Partials.MeetingSection')
@include('Public.ClubEvents.Partials.BookCallSection', ['bookCallimage' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Images/silicon-valley/silicon+valley+1.jpeg'])
@include('Public.ClubEvents.Default.Partials.HostInfoSection')
{{-- Check if this is visible in other clubs --}}
<livewire:magazine-section :eventId="$event?->id" />
@include('Public.ClubEvents.Default.Partials.ClubInfoSection')
@include('Public.ClubEvents.Default.Partials.ClubGallerySection', ['clubGalleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/1.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/2.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/3.jpg',
]
])
@include('Public.ClubEvents.Default.Partials.BannerSection-1')
@include('Public.ClubEvents.Default.Partials.GalleryCardSection')
@include('Public.ClubEvents.Default.Partials.BannerSection-2')
@include('Public.ClubEvents.Partials.DefaultBenefitsSection')
@include('Public.ClubEvents.Default.Partials.BannerSection-3')
@include('Public.ClubEvents.Default.Partials.TestimonialsSection')
@include('Public.ClubEvents.Partials.SectionThree')
@include('Public.ClubEvents.Partials.MemberSection')
@include('Public.ClubEvents.Default.Partials.GallerySection', ['galleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/paris/1.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/paris/2.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/paris/3.jpg',
]
])
@include('Public.ClubEvents.Partials.QuoteSection')
@include('Public.ClubEvents.Partials.LobbySection')
@endif
@include('Public.ClubEvents.Partials.FooterSection')

@endsection
@endforeach