@foreach($eventsData as $event)
@extends('Public.ClubEvents.Layouts.ClubPage')

@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionOne')
@include('Public.ClubEvents.Partials.TicketBookSection')
@include('Public.ClubEvents.Partials.QuoteSection')
@include('Public.ClubEvents.Partials.CountdownSection')
@include('Public.ClubEvents.Partials.DefaultSectionTwo', [
// 'otherInfo' => 'Public.ClubEvents.Partials.OtherInfo',
'sectionTwoDescription' => $event->section_two_description ?? "Paris here we come. This time with Global Woman Magazine to interview the amazing Virginie Guilhaume, TV personality and journalist. And so excited to have our members in Paris joining us at this exclusive mastermind with more guest speakers. You are invited too! If you are a woman entrepreneur, female founder or thinking of changing your career come and join us. You don’t need to be a member, you will be our guest and we will make you feel welcome and inspired to take actions and bring transformation in your life."
])
@include('Public.ClubEvents.Partials.LobbySection')
@include('Public.ClubEvents.Partials.QuoteSectionTwo')
@include('Public.ClubEvents.Default.Partials.AboutSection')
{{-- @include('Public.ClubEvents.Paris.Partials.MirelaSection') --}}

@include('Public.ClubEvents.Default.Partials.MeetingSection')
@include('Public.ClubEvents.Partials.BookCallSection', ['bookCallimage' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/Paris+Call.jpeg'])

{{-- @include('Public.ClubEvents.Paris.Partials.HostInfoSection') --}}

@include('Public.ClubEvents.Default.Partials.ClubInfoSection')
@include('Public.ClubEvents.Default.Partials.ClubGallerySection', ['clubGalleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/1.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/2.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/3.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/4.jpg',
]
])
@include('Public.ClubEvents.Default.Partials.GalleryCardSection')
@include('Public.ClubEvents.Partials.DefaultBenefitsSection')
@include('Public.ClubEvents.Paris.Partials.BannerSection')
@include('Public.ClubEvents.Paris.Partials.VideoSection')
@include('Public.ClubEvents.Paris.Partials.TestimonialsSection')
@include('Public.ClubEvents.Partials.SectionThree')
{{--
@include('Public.ClubEvents.Partials.MemberSection')
--}}
@include('Public.ClubEvents.Default.Partials.GallerySection', ['galleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/paris/1.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/paris/2.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/paris/3.jpg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.25.jpeg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.24.jpeg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.21+(1).jpeg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.22+(1).jpeg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.22.jpeg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.21.jpeg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.20+(1).jpeg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.20.jpeg',
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/WhatsApp+Image+2024-04-07+at+16.34.19+(1).jpeg',
]
])

@include('Public.ClubEvents.Partials.FooterSection')
@endsection

@endforeach