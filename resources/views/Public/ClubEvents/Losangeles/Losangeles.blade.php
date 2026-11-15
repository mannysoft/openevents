@foreach($eventsData as $event)
@extends('Public.ClubEvents.Layouts.ClubPage')

@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionOne')
@include('Public.ClubEvents.Losangeles.Partials.TextSection')
@include('Public.ClubEvents.Partials.TicketBookSection')
@include('Public.ClubEvents.Partials.QuoteSection')
@include('Public.ClubEvents.Partials.CountdownSection')
@include('Public.ClubEvents.Partials.DefaultSectionTwo')
@include('Public.ClubEvents.Partials.LobbySection')
@include('Public.ClubEvents.Partials.QuoteSectionTwo')
@include('Public.ClubEvents.Default.Partials.About1Section')
@include('Public.ClubEvents.Losangeles.Partials.MirelaSection')
@include('Public.ClubEvents.Default.Partials.MeetingSection')
@include('Public.ClubEvents.Partials.BookCallSection')
@include('Public.ClubEvents.Default.Partials.ClubInfoSection')
@include('Public.ClubEvents.Default.Partials.ClubGallerySection', ['clubGalleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/los-angeles/1.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/los-angeles/2.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/los-angeles/3.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/los-angeles/4.jpg',
]
])
@include('Public.ClubEvents.Default.Partials.GalleryCardSection')
@include('Public.ClubEvents.Partials.DefaultBenefitsSection')
@include('Public.ClubEvents.Default.Partials.BannerSection-1')
@include('Public.ClubEvents.Default.Partials.TestimonialsSection')
@include('Public.ClubEvents.Partials.SectionThree')
{{--
@include('Public.ClubEvents.Partials.MemberSection')
--}}
@include('Public.ClubEvents.Default.Partials.GallerySection', ['galleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/los-angeles/1.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/los-angeles/2.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/los-angeles/3.jpg'
]
])
@include('Public.ClubEvents.Partials.FooterSection')
@endsection

@endforeach