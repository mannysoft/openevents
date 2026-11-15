@use('App\Services\FormHtml')
<section id="organiser" class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="event_organiser_details" property="organizer" typeof="Organization">
                <div class="logo">
                    <img alt="{{$event->organiser->name}}" src="{{asset($event->organiser->full_logo_path)}}" property="logo">
                </div>
                    @if($event->organiser->enable_organiser_page)
                    <a class="organiser-name" href="{{route('showOrganiserHome', [$event->organiser->id, Str::slug($event->organiser->name)])}}" title="Organiser Page">
                        {{$event->organiser->name}}
                    </a>
                    @else
                        {{$event->organiser->name}}
                    @endif
                </h3>

                <p property="description">
                    {!! nl2br($event->organiser->about)!!}
                </p>
                <p>
                    @if($event->organiser->facebook)
                        <a property="sameAs" href="https://fb.com/{{$event->organiser->facebook}}" class="btn btn-facebook">
                            <i class="ico-facebook"></i>&nbsp; @lang("Public_ViewEvent.Facebook")
                        </a>
                    @endif
                        @if($event->organiser->twitter)
                            <a property="sameAs" href="https://twitter.com/{{$event->organiser->twitter}}" class="btn btn-twitter">
                                <i class="ico-twitter"></i>&nbsp; @lang("Public_ViewEvent.Twitter")
                            </a>
                        @endif
                    <button onclick="$(function(){ $('.contact_form').slideToggle(); });" type="button" class="btn btn-primary m-0">
                        <i class="ico-envelop"></i>&nbsp; @lang("Public_ViewEvent.Contact")
                    </button>
                </p>
                <div class="contact_form well well-sm">
                    {!! FormHtml::open(array('url' => route('postContactOrganiser', array('event_id' => $event->id)), 'class' => 'reset ajax')) !!}
                    <h3>@lang("Public_ViewEvent.Contact") {{$event->organiser->name}}</h3>
                    <div class="form-group">
                        {!! FormHtml::label(trans("Public_ViewEvent.your_name")) !!}
                        {!! FormHtml::text('name', null,
                            array('required',
                                  'class'=>'form-control',
                                  'placeholder'=>trans("Public_ViewEvent.your_name"))) !!}
                    </div>

                    <div class="form-group">
                        {!! FormHtml::label(trans("Public_ViewEvent.your_email_address")) !!}
                        {!! FormHtml::text('email', null,
                            array('required',
                                  'class'=>'form-control',
                                  'placeholder'=>trans("Public_ViewEvent.your_email_address"))) !!}
                    </div>

                    <div class="form-group">
                        {!! FormHtml::label(trans("Public_ViewEvent.your_message")) !!}
                        {!! FormHtml::textarea('message', null,
                            array('required',
                                  'class'=>'form-control',
                                  'placeholder'=>trans("Public_ViewEvent.your_message"))) !!}
                    </div>

                    <div class="form-group">
                        {!! FormHtml::submit(trans("Public_ViewEvent.send_message_submit"),
                          array('class'=>'btn btn-primary')) !!}
                    </div>
                </div>
                {!! FormHtml::close() !!}
            </div>
        </div>
    </div>
</section>
