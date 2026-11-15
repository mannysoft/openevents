@use('App\Services\FormHtml')
<div role="dialog"  class="modal fade" style="display: none;">

    @include('ManageOrganiser.Partials.EventCreateAndEditJS');

    {!! FormHtml::open(array('url' => route('postCreateEvent'), 'class' => 'ajax gf')) !!}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title">
                    <i class="ico-calendar"></i>
                    @lang("Event.create_event")</h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! FormHtml::label('title', trans("Event.event_title"), array('class'=>'control-label required')) !!}
                            {!! FormHtml::text('title', old('title'),array('class'=>'form-control','placeholder'=>trans("Event.event_title_placeholder", ["name"=>Auth::user()->first_name]) ))  !!}
                        </div>

                        <div class="form-group">
                            {!! FormHtml::label('lobby_page', 'Lobby Page', array('class'=>'control-label')) !!}
                            {!! FormHtml::select('lobby_page', $page_list,
                                old('lobby_page'),
                                ['class'=>'form-control']
                            ) !!}
                        </div>
                        <div class="form-group">
                            {!! FormHtml::label('club', 'Club', array('class'=>'control-label')) !!}
                            {!! FormHtml::select('club', $clubs,
                                old('club'),
                                ['class'=>'form-control']
                            ) !!}
                        </div>
                        <!-- <div class="form-group custom-theme">
                            {!! FormHtml::label('description', trans("Event.event_description"), array('class'=>'control-label required')) !!}
                            {!!  FormHtml::textarea('description', old('description'),
                                        array(
                                        'class'=>'form-control  editable',
                                        'rows' => 5
                                        ))  !!}
                        </div> -->
                        <div class="form-group custom-theme">
                            {!! FormHtml::label('description', trans("Event.event_description"), array('class'=>'control-label required')) !!}
                            {!!  FormHtml::textarea('description', old('description'),
                                        array(
                                        'class'=>'form-control',
                                        'rows' => 5,
                                        'name' => 'description',
                                        'id' => 'description'
                                        ))  !!}
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! FormHtml::label('start_date', trans("Event.event_start_date"), array('class'=>'required control-label')) !!}
                                    {!!  FormHtml::text('start_date', old('start_date'),
                                                        [
                                                    'class'=>'form-control start hasDatepicker ',
                                                    'data-field'=>'datetime',
                                                    'data-startend'=>'start',
                                                    'data-startendelem'=>'.end',
                                                    'readonly'=>''

                                                ])  !!}
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!!  FormHtml::label('end_date', trans("Event.event_end_date"),
                                                [
                                            'class'=>'required control-label '
                                        ])  !!}

                                    {!!  FormHtml::text('end_date', old('end_date'),
                                                [
                                            'class'=>'form-control end hasDatepicker ',
                                            'data-field'=>'datetime',
                                            'data-startend'=>'end',
                                            'data-startendelem'=>'.start',
                                            'readonly'=> ''
                                        ])  !!}
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            {!! FormHtml::label('event_image', trans("Event.event_image"), array('class'=>'control-label ')) !!}
                            {!! FormHtml::styledFile('event_image') !!}

                        </div>

						<div class="form-group">
                        <p class="control-label">{!! trans("Organiser.organiser_eventtype") !!}</p>
							{!! FormHtml::label('Venue', 'Venue', array('class'=>'control-label', 'id' => 'charge_yes')) !!}
							{{ FormHtml::radio('event_type', '0' , true,array('id'=>'venue')) }}
							&nbsp;&nbsp;
							{!! FormHtml::label('Virtual', 'Virtual', array('class'=>'control-label','id' => 'charge_no')) !!}
							{{ FormHtml::radio('event_type', '1' , false,array('id'=>'virtual')) }}
						</div>
						<!--  Virtual  -->
						<div class="virtual-form" style="display:none;">
							<div class="form-group">
								{!! FormHtml::label('eventLocation','Event Location', array('class'=>'control-label required')) !!}
								{!!  FormHtml::text('eventLocation', old('eventLocation'),array('class'=>'form-control','placeholder'=>trans("Event.event_title_placeholder", ["name"=>Auth::user()->first_name]) ))  !!}
							</div>
							<div class="form-group">
							<label class="control-label">Webinar?</label>&nbsp;&nbsp;
								{!! FormHtml::label('Yes', 'Yes', array('class'=>'control-label', 'id' => 'charge_yes')) !!}
								{{ FormHtml::radio('webinar', '1' , false,array('id'=>'1')) }}
								&nbsp;&nbsp;
								{!! FormHtml::label('No', 'No', array('class'=>'control-label','id' => 'charge_no')) !!}
								{{ FormHtml::radio('webinar', '0' , false,array('id'=>'0')) }}
							</div>
							<div class="form-group">
								{!! FormHtml::label('eventfunnelurl','Clickfunnel Url', array('class'=>'control-label required')) !!}
								{!!  FormHtml::text('eventfunnelurl', old('eventfunnelurl'),array('class'=>'form-control','placeholder'=>trans("Event.event_title_placeholder", ["name"=>Auth::user()->first_name]) ))  !!}
							</div>
							<div class="form-group">
                                            {!! FormHtml::label('timezone_id', trans("ManageAccount.timezone"), array('class'=>'control-label required')) !!}
                                            {!! FormHtml::select('timezone_id', $timezones, ['class' => 'form-control']) !!}
                            </div>

							<div class="form-group">
								<p class="control-label">{!! trans("Organiser.organiser_eventtype") !!}</p>
								{!! FormHtml::label('Lobby Live', 'Lobby Live', array('class'=>'control-label', 'id' => 'charge_yes')) !!}
								{{ FormHtml::radio('lobby_live', '1' , true,array('id'=>'lobbylive')) }}
								&nbsp;&nbsp;
								{!! FormHtml::label('Others', 'Others', array('class'=>'control-label','id' => 'charge_no')) !!}
								{{ FormHtml::radio('lobby_live', '0' , false,array('id'=>'others')) }}
							</div>

							<div class="virtual-form-others" style="display:none;">
								<div class="form-group">
									{!! FormHtml::label('location_virtual_others', trans("Event.virtual_location_others"), array('class'=>'control-label required ')) !!}
									{!!  FormHtml::text('location_virtual_others', old('location_virtual_others'), [
											'class'=>'form-control location_field',
											'placeholder'=>"E.g: Zoom Url,Google Meet Url etc..."
											])  !!}
								</div>
							</div>

						</div>


						<div class="venue-form">
                        @if(!empty(config("attendize.google_maps_geocoding_key")))
                        <div class="form-group address-automatic">
                            {!! FormHtml::label('name', trans("Event.venue_name"), array('class'=>'control-label required ')) !!}
                            {!!  FormHtml::text('venue_name_full', old('venue_name_full'),
                                        array(
                                        'class'=>'form-control geocomplete location_field',
                                        'placeholder'=>trans("Event.venue_name_placeholder")
                                        ))  !!}

                                    <!--These are populated with the Google places info-->
                            <div>
                                {!! FormHtml::hidden('formatted_address', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('street_number', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('country', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('country_short', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('place_id', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('name', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('location', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('postal_code', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('route', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('lat', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('lng', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('administrative_area_level_1', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('sublocality', '', ['class' => 'location_field']) !!}
                                {!! FormHtml::hidden('locality', '', ['class' => 'location_field']) !!}
                            </div>
                            <!-- /These are populated with the Google places info-->
                        </div>
                        <div class="address-manual" style="display:none;">
                            <h5>
                                @lang("Event.address_details")
                            </h5>

                            <div class="form-group">
                                {!! FormHtml::label('location_venue_name', trans("Event.venue_name"), array('class'=>'control-label required ')) !!}
                                {!!  FormHtml::text('location_venue_name', old('location_venue_name'), [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.venue_name_placeholder")
                                        ])  !!}
                            </div>
                            <div class="form-group">
                                {!! FormHtml::label('location_address_line_1', trans("Event.address_line_1"), array('class'=>'control-label')) !!}
                                {!!  FormHtml::text('location_address_line_1', old('location_address_line_1'), [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.address_line_1_placeholder")
                                        ])  !!}
                            </div>
                            <div class="form-group">
                                {!! FormHtml::label('location_address_line_2', trans("Event.address_line_2"), array('class'=>'control-label')) !!}
                                {!!  FormHtml::text('location_address_line_2', old('location_address_line_2'), [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.address_line_2_placeholder")
                                        ])  !!}
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! FormHtml::label('location_state', trans("Event.city"), array('class'=>'control-label')) !!}
                                        {!!  FormHtml::text('location_state', old('location_state'), [
                                                'class'=>'form-control location_field',
                                                'placeholder'=>trans("Event.city_placeholder")
                                                ])  !!}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! FormHtml::label('location_post_code', trans("Event.post_code"), array('class'=>'control-label')) !!}
                                        {!!  FormHtml::text('location_post_code', old('location_post_code'), [
                                                'class'=>'form-control location_field',
                                                'placeholder'=>trans("Event.post_code_placeholder")
                                                ])  !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span>
                            <a data-clear-field=".location_field"
                               data-toggle-class=".address-automatic, .address-manual"
                               data-show-less-text="@lang("Event.or(manual/existing_venue)") <b>@lang("Event.enter_existing")</b>" href="javascript:void(0);"
                               class="in-form-link show-more-options clear_location">
                            @lang("Event.or(manual/existing_venue)") <b>@lang("Event.enter_manual")</b>
                            </a>
                        </span>
                        @else
                        <div class="address-manual">
                            <h5>
                                @lang("Event.address_details")
                            </h5>

                            <div class="form-group">
                                {!! FormHtml::label('location_venue_name', trans("Event.venue_name"), array('class'=>'control-label required ')) !!}
                                {!!  FormHtml::text('location_venue_name', old('location_venue_name'), [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.venue_name_placeholder")
                                        ])  !!}
                            </div>
                            <div class="form-group">
                                {!! FormHtml::label('location_address_line_1', trans("Event.address_line_1"), array('class'=>'control-label')) !!}
                                {!!  FormHtml::text('location_address_line_1', old('location_address_line_1'), [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.address_line_1_placeholder")
                                        ])  !!}
                            </div>
                            <div class="form-group">
                                {!! FormHtml::label('location_address_line_2', trans("Event.address_line_2"), array('class'=>'control-label')) !!}
                                {!!  FormHtml::text('location_address_line_2', old('location_address_line_2'), [
                                        'class'=>'form-control location_field',
                                        'placeholder'=>trans("Event.address_line_2_placeholder")
                                        ])  !!}
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! FormHtml::label('location_state', trans("Event.city"), array('class'=>'control-label')) !!}
                                        {!!  FormHtml::text('location_state', old('location_state'), [
                                                'class'=>'form-control location_field',
                                                'placeholder'=>trans("Event.city_placeholder")
                                                ])  !!}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! FormHtml::label('location_post_code', trans("Event.post_code"), array('class'=>'control-label')) !!}
                                        {!!  FormHtml::text('location_post_code', old('location_post_code'), [
                                                'class'=>'form-control location_field',
                                                'placeholder'=>trans("Event.post_code_placeholder")
                                                ])  !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
						</div>
                        @if($organiser_id)
                            {!! FormHtml::hidden('organiser_id', $organiser_id) !!}
                        @else
                            <div class="create_organiser" style="{{$organisers->isEmpty() ? '' : 'display:none;'}}">
                                <h5>
                                    @lang("Organiser.organiser_details")
                                </h5>

                                <div class="form-group">
                                    {!! FormHtml::label('organiser_name', trans("Organiser.organiser_name"), array('class'=>'required control-label ')) !!}
                                    {!!  FormHtml::text('organiser_name', old('organiser_name'),
                                                array(
                                                'class'=>'form-control',
                                                'placeholder'=>trans("Organiser.organiser_name_placeholder")
                                                ))  !!}
                                </div>
                                <div class="form-group">
                                    {!! FormHtml::label('organiser_email', trans("Organiser.organiser_email"), array('class'=>'control-label required')) !!}
                                    {!!  FormHtml::text('organiser_email', old('organiser_email'),
                                                array(
                                                'class'=>'form-control ',
                                                'placeholder'=>trans("Organiser.organiser_email_placeholder")
                                                ))  !!}
                                </div>
                                <div class="form-group">
                                    {!! FormHtml::label('organiser_about', trans("Organiser.organiser_description"), array('class'=>'control-label ')) !!}
                                    {!!  FormHtml::textarea('organiser_about', old('organiser_about'),
                                                array(
                                                'class'=>'form-control editable2',
                                                'placeholder'=>trans("Organiser.organiser_description_placeholder"),
                                                'rows' => 4
                                                ))  !!}
                                </div>

                                <div class="form-group more-options">
                                    {!! FormHtml::label('organiser_logo', trans("Organiser.organiser_logo"), array('class'=>'control-label ')) !!}
                                    {!! FormHtml::styledFile('organiser_logo') !!}
                                </div>
                                <div class="row more-options">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! FormHtml::label('organiser_facebook', trans("Organiser.organiser_facebook"), array('class'=>'control-label ')) !!}
                                            {!!  FormHtml::text('organiser_facebook', old('organiser_facebook'),
                                                array(
                                                'class'=>'form-control ',
                                                'placeholder'=>trans("Organiser.organiser_facebook_placeholder")
                                                ))  !!}

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! FormHtml::label('organiser_twitter', trans("Organiser.organiser_twitter"), array('class'=>'control-label ')) !!}
                                            {!!  FormHtml::text('organiser_twitter', old('organiser_twitter'),
                                                array(
                                                'class'=>'form-control ',
                                                'placeholder'=>trans("Organiser.organiser_twitter_placeholder")
                                                ))  !!}

                                        </div>
                                    </div>
                                </div>

                                <a data-show-less-text="@lang("Organiser.hide_additional_organiser_options")" href="javascript:void(0);"
                                   class="in-form-link show-more-options">
                                    @lang("Organiser.additional_organiser_options")
                                </a>
                            </div>

                            @if(!$organisers->isEmpty())
                                <div class="form-group select_organiser" style="{{$organisers ? '' : 'display:none;'}}">

                                    {!! FormHtml::label('organiser_id', trans("Organiser.select_organiser"), array('class'=>'control-label ')) !!}
                                    {!! FormHtml::select('organiser_id', $organisers, $organiser_id, ['class' => 'form-control']) !!}

                                </div>
                                <span class="">
                                    @lang("Organiser.or") <a data-toggle-class=".select_organiser, .create_organiser"
                                       data-show-less-text="<b>@lang("Organiser.select_an_organiser")</b>" href="javascript:void(0);"
                                       class="in-form-link show-more-options">
                                        <b>@lang("Organiser.create_an_organiser")</b>
                                    </a>
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="uploadProgress"></span>
                {!! FormHtml::button(trans("basic.cancel"), ['class'=>"btn modal-close btn-danger",'data-dismiss'=>'modal']) !!}
                {!! FormHtml::submit(trans("Event.create_event"), ['class'=>"btn btn-success"]) !!}
            </div>
        </div>
        {!! FormHtml::close() !!}
    </div>
</div>
<script>
tinymce.init({
  selector: '#description',
  branding: false,
  height: 270,
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
<script>
$(document).ready(function(){
     $('#virtual').click(function(){
		$('.venue-form').css("display","none");
		$('.virtual-form').css("display","block");

    });
	$('#venue').click(function(){
		$('.venue-form').css("display","block");
		$('.virtual-form').css("display","none");
    });
	$('#others').click(function(){
		$('.virtual-form-others').css("display","block");
    });
});
</script>
