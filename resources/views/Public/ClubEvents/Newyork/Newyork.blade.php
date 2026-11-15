@foreach($eventsData as $event)
@extends('Public.ClubEvents.Layouts.ClubPage')

@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionOne')
@include('Public.ClubEvents.Partials.TicketBookSection')
@include('Public.ClubEvents.Partials.QuoteSection')

@include('Public.ClubEvents.Partials.CountdownSection')
@include('Public.ClubEvents.Partials.DefaultSectionTwo')
@include('Public.ClubEvents.Partials.LobbySection')
@include('Public.ClubEvents.Partials.QuoteSectionTwo')
@include('Public.ClubEvents.Default.Partials.AboutSection')
<!--@include('Public.ClubEvents.Newyork.Partials.VenueCardSection') -->
@include('Public.ClubEvents.Newyork.Partials.MirelaSection')
@include('Public.ClubEvents.Default.Partials.MeetingSection', ['videos' =>
[
[
'type' => 's3',
'poster' => 'https://img.youtube.com/vi/S1W0SC7KV9c/maxresdefault.jpg',
'video' => "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/New+York/Harmonizing+Empowerment_+Global+Woman's+Musical+Journey.mp4"
],
[
'type' => 's3',
'poster' => 'https://img.youtube.com/vi/SkNSpXHtlNA/maxresdefault.jpg',
'video' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/New+York/Empowering+Global+Voices_+Inspiring+Positive+Energy+and+Resilience.mp4'
],
[
'type' => 's3',
'poster' => 'https://img.youtube.com/vi/LwUMWBi-mT0/maxresdefault.jpg',
'video' => "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/New+York/1Global+Woman+Club+NYC-+September+2023.mp4"
],
[
'type' => 's3',
'poster' => 'https://img.youtube.com/vi/Q4GiC8Xmu7M/maxresdefault.jpg',
'video' => "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/New+York/2Global+Woman+Club+in+NYC.mp4"
],
[
'type' => 's3',
'poster' => 'https://img.youtube.com/vi/RCEBqlD2QYI/maxresdefault.jpg',
'video' => "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/New+York/3How+to+Boost+Confidence+_+Get+a+Free+Facial!+with+Dr.+Lanna+Cheuck.mp4"
],
[
'type' => 's3',
'poster' => 'https://img.youtube.com/vi/ocZ_BlkaMPI/maxresdefault.jpg',
'video' => "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/New+York/4Village+Girl+to+Restaurant+Tycoon_+Liene+Uresina+Success+Story.mp4"
],
]
])
@include('Public.ClubEvents.Partials.BookCallSection', ['bookCallimage' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/bookcall.jpeg'])
@include('Public.ClubEvents.Newyork.Partials.HostInfoSection')
@include('Public.ClubEvents.Default.Partials.ClubInfoSection')
@include('Public.ClubEvents.Default.Partials.ClubGallerySection', ['clubGalleryImages' => [
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b2a40e5f6c1c614f30ce7f.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b39064a716b3fa14049801.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b390648b23d7d800c4e1a9.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b3902b8b23d7e251c4e0ba.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b3903ca716b30b670497d5.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b2a5a9b72b591ea1070b64.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b2a5d6095ec02f7a28e74e.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b2a427c8cfa18ccfb91961.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b2a5eec8cfa152e4b91a75.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b3a536eed6587388683e79.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b3a536a716b36af204bb8e.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65b3a5368b23d71521c50a9f.jpeg',
]
])
@include('Public.ClubEvents.Default.Partials.GalleryCardSection')
@include('Public.ClubEvents.Partials.DefaultBenefitsSection')
@include('Public.ClubEvents.Newyork.Partials.BannerSection')
@include('Public.ClubEvents.Newyork.Partials.TestimonialsSection')
@include('Public.ClubEvents.Partials.SectionThree', ['showSectionThree' => true])
@include('Public.ClubEvents.Partials.MemberSection')
@include('Public.ClubEvents.Default.Partials.GallerySection', ['galleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/new-york/1.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/new-york/2.jpg',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/new-york/3.jpg'
]
])
@include('Public.ClubEvents.Partials.FooterSection')
@endsection

@endforeach