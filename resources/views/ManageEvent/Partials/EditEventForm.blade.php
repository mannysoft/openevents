@include('ManageOrganiser.Partials.EventCreateAndEditJS')
@use('App\Services\FormHtml')
{!! FormHtml::model($event, array('url' => route('postEditEvent', ['event_id' => $event->id]), 'class' => 'ajax gf')) !!}

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
          {!! FormHtml::label('currency_id', trans("ManageEvent.default_currency"), array('class'=>'control-label required')) !!}
          {!! FormHtml::select('currency_id', $currencies, $event->currency_id, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            {!! FormHtml::label('is_live', trans("Event.event_visibility"), array('class'=>'control-label required')) !!}
            {!!  FormHtml::select('is_live', [
            '1' => trans("Event.vis_public"),
            '0' => trans("Event.vis_hide")],null,
                                        array(
                                        'class'=>'form-control'
                                        ))  !!}
        </div>
        <div class="form-group">
            {!! FormHtml::label('title', trans("Event.event_title"), array('class'=>'control-label required')) !!}
            {!!  FormHtml::text('title', old('title'),
                                        array(
                                        'class'=>'form-control',
                                        'placeholder'=>trans("Event.event_title_placeholder", ["name"=>Auth::user()->first_name])
                                        ))  !!}
        </div>

        <!-- <div class="form-group">
           {!! FormHtml::label('description', trans("Event.event_description"), array('class'=>'control-label')) !!}
            {!!  FormHtml::textarea('description', old('description'),
                                        array(
                                        'class'=>'form-control editable',
                                        'rows' => 5
                                        ))  !!}
        </div> -->
        <div class="form-group">
           {!! FormHtml::label('description', trans("Event.event_description"), array('class'=>'control-label')) !!}
            {!!  FormHtml::textarea('description', old('description'),
                                        array(
                                        'class'=>'form-control',
                                        'rows' => 5,
                                        'name' => 'description',
                                        'id' => 'description'
                                        ))  !!}
        </div>

        <div class="form-group address-automatic" style="display:{{$event->location_is_manual ? 'none' : 'block'}};">
            {!! FormHtml::label('name', trans("Event.venue_name"), array('class'=>'control-label required ')) !!}
            {!!  FormHtml::text('venue_name_full', old('venue_name_full'),
                                        array(
                                        'class'=>'form-control geocomplete location_field',
                                        'placeholder'=>trans("Event.venue_name_placeholder")//'E.g: The Crab Shack'
                                        ))  !!}

            <!--These are populated with the Google places info-->
            <div>
               {!! FormHtml::hidden('formatted_address', $event->location_address, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('street_number', $event->location_street_number, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('country', $event->location_country, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('country_short', $event->location_country_short, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('place_id', $event->location_google_place_id, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('name', $event->venue_name, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('location', '', ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('postal_code', $event->location_post_code, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('route', $event->location_address_line_1, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('lat', $event->location_lat, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('lng', $event->location_long, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('administrative_area_level_1', $event->location_state, ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('sublocality', '', ['class' => 'location_field']) !!}
               {!! FormHtml::hidden('locality', $event->location_address_line_1, ['class' => 'location_field']) !!}
            </div>
            <!-- /These are populated with the Google places info-->

        </div>

        <div class="address-manual" style="display:{{$event->location_is_manual ? 'block' : 'none'}};">
            <div class="form-group">
                {!! FormHtml::label('location_venue_name', trans("Event.venue_name"), array('class'=>'control-label required ')) !!}
                {!!  FormHtml::text('location_venue_name', $event->venue_name, [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.venue_name_placeholder") // same as above
                            ])  !!}
            </div>
            <div class="form-group">
                {!! FormHtml::label('location_address_line_1', trans("Event.address_line_1"), array('class'=>'control-label')) !!}
                {!!  FormHtml::text('location_address_line_1', $event->location_address_line_1, [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.address_line_1_placeholder")//'E.g: 45 Grafton St.'
                            ])  !!}
            </div>
            <div class="form-group">
                {!! FormHtml::label('location_address_line_2', trans("Event.address_line_2"), array('class'=>'control-label')) !!}
                {!!  FormHtml::text('location_address_line_2', $event->location_address_line_2, [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.address_line_2_placeholder")//'E.g: Dublin.'
                            ])  !!}
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! FormHtml::label('location_state', trans("Event.city"), array('class'=>'control-label')) !!}
                        {!!  FormHtml::text('location_state', $event->location_state, [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.city_placeholder")//'E.g: Dublin.'
                            ])  !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! FormHtml::label('location_post_code', trans("Event.post_code"), array('class'=>'control-label')) !!}
                        {!!  FormHtml::text('location_post_code', $event->location_post_code, [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.post_code_placeholder")// 'E.g: 94568.'
                            ])  !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix" style="margin-top:-10px; padding: 5px; padding-top: 0px;">
            <span class="pull-right">
                @lang("Event.or(manual/existing_venue)") <a data-clear-field=".location_field" data-toggle-class=".address-automatic, .address-manual" data-show-less-text="{{$event->location_is_manual ? trans("Event.enter_manual"):trans("Event.enter_existing")}}" href="javascript:void(0);" class="show-more-options clear_location">{{$event->location_is_manual ? trans("Event.enter_existing"):trans("Event.enter_manual")}}</a>
            </span>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! FormHtml::label('start_date', trans("Event.event_start_date"), array('class'=>'required control-label')) !!}
                    {!!  FormHtml::text('start_date', $event->getFormattedDate('start_date'),
                                                        [
                                                    'class'=>'form-control start hasDatepicker ',
                                                    'data-field'=>'datetime',
                                                    'data-startend'=>'start',
                                                    'data-startendelem'=>'.end',
                                                    'readonly'=>''

                                                ])  !!}
                </div>
            </div>

            <div class="col-sm-6 ">
                <div class="form-group">
                    {!!  FormHtml::label('end_date', trans("Event.event_end_date"),
                                        [
                                    'class'=>'required control-label '
                                ])  !!}
                    {!!  FormHtml::text('end_date', $event->getFormattedDate('end_date'),
                                                [
                                            'class'=>'form-control end hasDatepicker ',
                                            'data-field'=>'datetime',
                                            'data-startend'=>'end',
                                            'data-startendelem'=>'.start',
                                            'readonly'=>''
                                        ])  !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                   {!! FormHtml::label('event_image', trans("Event.event_flyer"), array('class'=>'control-label ')) !!}
                   {!! FormHtml::styledFile('event_image', 1) !!}
                </div>

                @if($event->images->count())
                    <div class="form-group">
                        {!! FormHtml::label('event_image_position', trans("Event.event_image_position"), array('class'=>'control-label')) !!}
                        {!! FormHtml::select('event_image_position', [
                                '' => trans("Event.event_image_position_hide"),
                                'before' => trans("Event.event_image_position_before"),
                                'after' => trans("Event.event_image_position_after"),
                                'left' => trans("Event.event_image_position_left"),
                                'right' => trans("Event.event_image_position_right"),
                            ],
                            old('event_image_position'),
                            ['class'=>'form-control']
                        ) !!}
                    </div>
                    {!! FormHtml::label('', trans("Event.current_event_flyer"), array('class'=>'control-label ')) !!}
                    <div class="form-group">
                        <div class="well well-sm well-small">
                           {!! FormHtml::label('remove_current_image', trans("Event.delete?"), array('class'=>'control-label ')) !!}
                           {!! FormHtml::checkbox('remove_current_image') !!}

                        </div>
                    </div>
                @endif
            </div>
            <div class="col-md-6">
                <div class="float-l">
                    @if($event->images->count())
                    <div class="thumbnail">
                       {!!FormHtml::image('/'.$event->images->first()['image_path'])!!}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! FormHtml::label('google_tag_manager_code', trans("Organiser.google_tag_manager_code"), ['class'=>'control-label']) !!}
                    {!!  FormHtml::text('google_tag_manager_code', old('google_tag_manager_code'), [
                            'class'=>'form-control',
                            'placeholder' => trans("Organiser.google_tag_manager_code_placeholder"),
                        ])
                    !!}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="panel-footer mt15 text-right">
           {!! FormHtml::hidden('organiser_id', $event->organiser_id) !!}
           {!! FormHtml::submit(trans("Event.save_changes"), ['class'=>"btn btn-success"]) !!}
        </div>
    </div>
    {!! FormHtml::close() !!}
</div>
<script>
tinymce.init({
  selector: '#description',
  branding: false,
  height: 370,
  entity_encoding : "raw",
  paste_data_images: true,
  image_advtab: true,
  toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
  toolbar2: "print preview media | forecolor backcolor",
  plugins: [
      "advlist autolink lists link image charmap print preview hr anchor pagebreak",
      "searchreplace wordcount visualblocks visualchars code fullscreen",
      "insertdatetime media nonbreaking save table contextmenu directionality",
      "template paste textcolor colorpicker textpattern"
    ],
});
</script>
