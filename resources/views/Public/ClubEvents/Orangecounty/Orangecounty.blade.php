@foreach($eventsData as $event)
@extends('Public.ClubEvents.Layouts.ClubPage')

@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionOne')
@include('Public.ClubEvents.Partials.TicketBookSection')
@include('Public.ClubEvents.Partials.QuoteSection')
@include('Public.ClubEvents.Partials.CountdownSection')
@include('Public.ClubEvents.Partials.DefaultSectionTwo', ['sectionTwoDescription' => "At Global Woman Club, we believe in more than just networking; we believe in the power of connection, collaboration, and empowerment. Our community spans the globe, and by joining our club in Orange County, California, you become part of a worldwide movement dedicated to elevating women in every corner of the planet."])
@include('Public.ClubEvents.Partials.LobbySection')
@include('Public.ClubEvents.Partials.QuoteSectionTwo')
@include('Public.ClubEvents.Default.Partials.AboutSection')
{{-- @include('Public.ClubEvents.Orangecounty.Partials.MirelaSection') --}}
@include('Public.ClubEvents.Default.Partials.MeetingSection')
@include('Public.ClubEvents.Partials.BookCallSection')
@include('Public.ClubEvents.Default.Partials.HostInfoSection')
@include('Public.ClubEvents.Default.Partials.ClubInfoSection')
@include('Public.ClubEvents.Default.Partials.ClubGallerySection', ['clubGalleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/orange-county/01.png',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/orange-county/02.png',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/orange-county/03.png',
'https://davxegdvkjbks.cloudfront.net/club-images/305x203/orange-county/04.png',
]
])
@include('Public.ClubEvents.Default.Partials.GalleryCardSection')
@include('Public.ClubEvents.Partials.DefaultBenefitsSection')
@include('Public.ClubEvents.Default.Partials.BannerSection-1')
@include('Public.ClubEvents.Default.Partials.TestimonialsSection')
@include('Public.ClubEvents.Partials.SectionThree')
@include('Public.ClubEvents.Partials.MemberSection')

@include('Public.ClubEvents.Default.Partials.GallerySection', ['galleryImages' => [
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/Orange-county/08.png',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/Orange-county/09.png',
'https://davxegdvkjbks.cloudfront.net/club-images/402x268/Orange-county/10.png',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65c3ab3e9f46291818955c46.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65c64ad0b8c0db2b48300bc7.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65c64ad089e6786ddb4da710.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65c3ab3e16d2932d1aaf453a.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65c3ab3eb7a76dfba1f119bb.jpeg',
'https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65c64ad0de0b9fe5beb7fc11.jpeg',
]
])

@include('Public.ClubEvents.Partials.FooterSection')
@endsection

@endforeach