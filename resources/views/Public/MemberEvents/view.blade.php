@extends('Public.ClubEvents.Layouts.MemberPage')

@section('content')
@include('Public.ClubEvents.Partials.HeaderSection')
@include('Public.ClubEvents.Partials.SectionTitle')
@include('Public.ClubEvents.Partials.MemberSectionTwo')
<!-- <br><br>
<div class="container">
    <div class="row">
        <div class="col-lg-6 col-sm-12 align-self-center">
            <img src="{{ $event->postImage }}" />
            <br>
        </div>
        <div class="col-lg-6 col-sm-12 align-self-center">
        </div>
    </div>

    <br>
    <p style="font-family:'loto';font-size:22px">{!! $event->postDescription !!}</p>
</div> -->
@include('Public.ClubEvents.Partials.FooterSection')
@endsection