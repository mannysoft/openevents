@extends('Shared.Layouts.MasterWithoutMenus')
@use('App\Services\FormHtml')
@section('title')
    @lang("Organiser.create_organiser")
@stop

@section('head')
    <style>
        .modal-header {
            background-color: transparent !important;
            color: #666 !important;
            text-shadow: none !important;;
        }
    </style>
    <script>
    @include('ManageOrganiser.Partials.OrganiserCreateAndEditJS')
    </script>

@stop

@section('content')
    <div class="row">
        <div class="col-md-7 col-md-offset-2">
            <div class="panel">
                <div class="panel-body">
                    <div class="logo">
                        {!!FormHtml::image('assets/images/logo-dark.png')!!}
                    </div>
                    <h2>@lang("Organiser.create_organiser")</h2>

                    {!! FormHtml::open(array('url' => route('postCreateOrganiser'), 'class' => 'ajax')) !!}
                    @if(@$_GET['first_run'] == '1')
                        <div class="alert alert-info">
                            @lang("Organiser.create_organiser_text")
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! FormHtml::label('name', trans("Organiser.organiser_name"), array('class'=>'required control-label ')) !!}
                                {!!  FormHtml::text('name', old('name'),
                                            array(
                                            'class'=>'form-control'
                                            ))  !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! FormHtml::label('email', trans("Organiser.organiser_email"), array('class'=>'control-label required')) !!}
                                {!!  FormHtml::text('email', old('email'),
                                            array(
                                            'class'=>'form-control ',
                                            'placeholder'=>trans("Organiser.organiser_email_placeholder")
                                            ))  !!}
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        {!! FormHtml::label('about', trans("Organiser.organiser_description"), array('class'=>'control-label ')) !!}
                        {!!  FormHtml::textarea('about', old('about'),
                                    array(
                                    'class'=>'form-control ',
                                    'placeholder'=>trans("Organiser.organiser_description_placeholder"),
                                    'rows' => 4
                                    ))  !!}
                    </div>
                    <div class="form-group">
                        <p class="control-label">{!! trans("Organiser.organiser_tax_prompt") !!}</p>
                        {!! FormHtml::label('Yes', 'Yes', array('class'=>'control-label', 'id' => 'charge_yes')) !!}
                        {{ FormHtml::radio('charge_tax', '1' , false) }}
                        {!! FormHtml::label('No', 'No', array('class'=>'control-label','id' => 'charge_no')) !!}
                        {{ FormHtml::radio('charge_tax', '0' , true) }}
                    </div>

                    <div id="tax_fields" class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! FormHtml::label('tax_id', trans("Organiser.organiser_tax_id"), array('class'=>'control-label')) !!}
                                {!! FormHtml::text('tax_id', old('tax_id'), array('class'=>'form-control', 'placeholder'=>'Tax ID'))  !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! FormHtml::label('tax_name', trans("Organiser.organiser_tax_name"), array('class'=>'control-label')) !!}
                                {!! FormHtml::text('tax_name', old('tax_name'), array('class'=>'form-control', 'placeholder'=>'Tax name'))  !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! FormHtml::label('tax_value', trans("Organiser.organiser_tax_value"), array('class'=>'control-label')) !!}
                                {!! FormHtml::text('tax_value', old('tax_value'), array('class'=>'form-control', 'placeholder'=>'Tax Value'))  !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! FormHtml::label('facebook', trans("Organiser.organiser_facebook"), array('class'=>'control-label ')) !!}

                                <div class="input-group">
                                    <span style="background-color: #eee;" class="input-group-addon">facebook.com/</span>
                                    {!!  FormHtml::text('facebook', old('facebook'),
                                                    array(
                                                    'class'=>'form-control ',
                                                    'placeholder'=>trans("Organiser.organiser_username_facebook_placeholder")
                                                    ))  !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! FormHtml::label('twitter', trans("Organiser.organiser_twitter"), array('class'=>'control-label ')) !!}

                                <div class="input-group">
                                    <span style="background-color: #eee;" class="input-group-addon">twitter.com/</span>
                                    {!!  FormHtml::text('twitter', old('twitter'),
                                             array(
                                             'class'=>'form-control ',
                                                    'placeholder'=>trans("Organiser.organiser_username_twitter_placeholder")
                                             ))  !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        {!! FormHtml::label('organiser_logo', trans("Organiser.organiser_logo"), array('class'=>'control-label ')) !!}
                        {!! FormHtml::styledFile('organiser_logo') !!}
                    </div>

                    {!! FormHtml::submit(trans("Organiser.create_organiser"), ['class'=>" btn-block btn btn-success"]) !!}
                    {!! FormHtml::close() !!}
                </div>
            </div>
        </div>
    </div>
@stop
