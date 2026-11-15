@extends('Shared.Layouts.MasterWithoutMenus')

@section('title')
    Invalid Link
@stop

@section('content')
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="panel">
                <div class="panel-body" style="text-align: center;">
                    <h4>This invitation link is invalid or has expired.</h4>
                    <p>Please request a new invitation link from the event organizer.</p>
                    <a href="{{ url('/') }}" class="btn btn-primary">Go to Homepage</a>
                </div>
            </div>
        </div>
    </div>
@stop
