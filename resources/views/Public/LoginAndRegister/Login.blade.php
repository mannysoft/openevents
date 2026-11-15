@extends('Shared.Layouts.MasterWithoutMenus')

@section('title', trans("User.login"))
@if (Auth::check())
{{ redirect()->to(route('showSelectOrganiser'))->send()}}
@endif

@use('App\Services\FormHtml')
@section('content')
<form action="{{ route("login") }}">
<div class="row">
    <div class="col-md-4 col-md-offset-4">
        <div class="panel">
            <div class="panel-body">
                <div class="logo">
                    {!!FormHtml::image('assets/images/logo-dark.png')!!}
                </div>

                <div class="form-group">
                    <!--<button type="submit" class="btn btn-block btn-success">@lang("User.login")</button>-->
                    <a class="btn btn-block btn-success text-white" href="https://lobby.globalwomanclub.com/oauth?app_id={{ config('services.lobby_app.app_id') }}">Continue With Events</a>
                </div>
            </div>
        </div>
    </div>
</div>
</form>
@stop