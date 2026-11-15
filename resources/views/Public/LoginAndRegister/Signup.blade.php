@extends('Shared.Layouts.MasterWithoutMenus')
@use('App\Services\FormHtml')
@section('title')
    @lang("User.sign_up")
@stop

@section('content')
    <div class="row">
        <div class="col-md-7 col-md-offset-2">
            {!! FormHtml::open(array('url' => route("showSignup"), 'class' => 'panel')) !!}
            <div class="panel-body">
                <div class="logo">
                   {!! FormHtml::image('assets/images/logo-dark.png') !!}
                </div>
                <h2>@lang("User.sign_up")</h2>

                @if(Request::input('first_run'))
                    <div class="alert alert-info">
                        @lang("User.sign_up_first_run")
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group {{ ($errors->has('first_name')) ? 'has-error' : '' }}">
                            {!! FormHtml::label('first_name', trans("User.first_name"), ['class' => 'control-label required']) !!}
                            {!! FormHtml::text('first_name', null, ['class' => 'form-control']) !!}
                            @if($errors->has('first_name'))
                                <p class="help-block">{{ $errors->first('first_name') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group {{ ($errors->has('last_name')) ? 'has-error' : '' }}">
                            {!! FormHtml::label('last_name', trans("User.last_name"), ['class' => 'control-label']) !!}
                            {!! FormHtml::text('last_name', null, ['class' => 'form-control']) !!}
                            @if($errors->has('last_name'))
                                <p class="help-block">{{ $errors->first('last_name') }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-group {{ ($errors->has('email')) ? 'has-error' : '' }}">
                    {!! FormHtml::label('email', trans("User.email"), ['class' => 'control-label required']) !!}
                    {!! FormHtml::text('email', null, ['class' => 'form-control']) !!}
                    @if($errors->has('email'))
                        <p class="help-block">{{ $errors->first('email') }}</p>
                    @endif
                </div>
                <div class="form-group {{ ($errors->has('password')) ? 'has-error' : '' }}">
                    {!! FormHtml::label('password', trans("User.password"), ['class' => 'control-label required']) !!}
                    {!! FormHtml::password('password',  ['class' => 'form-control']) !!}
                    @if($errors->has('password'))
                        <p class="help-block">{{ $errors->first('password') }}</p>
                    @endif
                </div>
                <div class="form-group {{ ($errors->has('password_confirmation')) ? 'has-error' : '' }}">
                    {!! FormHtml::label('password_confirmation', 'Password again', ['class' => 'control-label required']) !!}
                    {!! FormHtml::password('password_confirmation',  ['class' => 'form-control']) !!}
                    @if($errors->has('password_confirmation'))
                        <p class="help-block">{{ $errors->first('password_confirmation') }}</p>
                    @endif
                </div>

                @if(Utils::isAttendize())
                <div class="form-group {{ ($errors->has('terms_agreed')) ? 'has-error' : '' }}">
                    <div class="checkbox custom-checkbox">
                        {!! FormHtml::checkbox('terms_agreed', old('terms_agreed'), false, ['id' => 'terms_agreed']) !!}
                        {!! FormHtml::rawLabel('terms_agreed', trans("User.terms_and_conditions", ["url"=>route('termsAndConditions')])) !!}
                        @if ($errors->has('terms_agreed'))
                            <p class="help-block">{{ $errors->first('terms_agreed') }}</p>
                        @endif
                    </div>
                </div>
                @endif

                <div class="form-group ">
                   {!! FormHtml::submit(trans("User.sign_up"), array('class'=>"btn btn-block btn-success")) !!}
                </div>
                    <div class="signup">
                        <span>{!! @trans("User.already_have_account", ["url"=>route("login")]) !!}</span>
                    </div>
            </div>
            {!! FormHtml::close() !!}
        </div>
    </div>
@stop
