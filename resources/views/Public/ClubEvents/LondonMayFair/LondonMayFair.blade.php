@foreach($eventsData as $event)
@extends('Public.ClubEvents.Layouts.ClubPage')

@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionOne')
@include('Public.ClubEvents.Partials.TicketBookSection')
@include('Public.ClubEvents.Partials.QuoteSection')
@include('Public.ClubEvents.Partials.DefaultSectionTwo')
@include('Public.ClubEvents.Default.Partials.About1Section')
@include('Public.ClubEvents.Partials.LobbySection')
@include('Public.ClubEvents.Default.Partials.HostInfoSection')
@include('Public.ClubEvents.Default.Partials.MeetingSection', ['videos' =>
[
[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/vybbCkiVT2lXNtLX/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/DHCRXxLAapc?si=vybbCkiVT2lXNtLX&amp;controls=0"
],
[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/SHqKYJNjEW4QKHq3/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/lM3w04SYDe8?si=SHqKYJNjEW4QKHq3&amp;controls=0"
],

[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/JBu22nAlYxe6xZ5z/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/UT6LxXITDNE?si=JBu22nAlYxe6xZ5z&amp;controls=0"
],

[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/6u5OWqEzjtmAdjVp/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/32JmEyri8rs?si=6u5OWqEzjtmAdjVp&amp;controls=0"
],

[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/9Jk-1TUux3Wvk7Dg/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/106r_gmXMA4?si=9Jk-1TUux3Wvk7Dg&amp;controls=0"
],

[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/6BFzp-za4Dt8yiJl/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/2xgiZjhCXvo?si=6BFzp-za4Dt8yiJl&amp;controls=0"
],

[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/ZDqTvY_6_ipiNWc_/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/zLzWZ82B42w?si=ZDqTvY_6_ipiNWc_&amp;controls=0"
],


[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/K5_KKss8f_UBiFi6/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/yIukWKsE1ao?si=K5_KKss8f_UBiFi6&amp;controls=0"
],

[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/Ntzgg52FeVVpsp2G/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/x2D__-kG1Ko?si=Ntzgg52FeVVpsp2G&amp;controls=0"
],

[
'type' => 'youtube',
'poster' => 'https://img.youtube.com/vi/Ol7OSQvhDWt2hdVc/maxresdefault.jpg',
'video' => "https://www.youtube.com/embed/YAot10qXkZU?si=Ol7OSQvhDWt2hdVc&amp;controls=0"
],
]
])
@include('Public.ClubEvents.Partials.BookCallSection', ['bookCallimage' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/WhatsApp+Image+2024-04-01+at+20.34.23.jpeg'])
@include('Public.ClubEvents.Partials.SectionThree1')
@include('Public.ClubEvents.Default.Partials.ClubInfoSection')
{{--
@include('Public.ClubEvents.Default.Partials.ClubGallerySection', ['clubGalleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/1.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/2.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/3.jpg',
]
])
--}}
@include('Public.ClubEvents.Default.Partials.GalleryCardSection')
@include('Public.ClubEvents.Partials.DefaultBenefitsSection')
@include('Public.ClubEvents.Default.Partials.TestimonialsSection'))

@include('Public.ClubEvents.Default.Partials.GallerySection', ['galleryImages' => [
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/WhatsApp+Image+2024-04-01+at+20.36.12.jpeg',
]
])
@include('Public.ClubEvents.Partials.FooterSection')
@endsection

@endforeach