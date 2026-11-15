@use('App\Services\FormHtml')
<div role="dialog"  class="modal fade " style="display: none;">
   {!! FormHtml::open(array('url' => route('postInviteAttendee', array('event_id' => $event->id)), 'class' => 'ajax')) !!}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3 class="modal-title">
                    <i class="ico-user"></i>
                    @lang("ManageEvent.invite_attendee")</h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                   {!! FormHtml::label('ticket_id', trans("ManageEvent.ticket"), array('class'=>'control-label required')) !!}
                                   {!! FormHtml::select('ticket_id', $tickets, null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                {!! FormHtml::label('first_name', trans("Attendee.first_name"), array('class'=>'control-label required')) !!}

                                {!!  FormHtml::text('first_name', old('first_name'),
                                            array(
                                            'class'=>'form-control'
                                            ))  !!}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                {!! FormHtml::label('last_name', trans("Attendee.last_name"), array('class'=>'control-label')) !!}

                                {!!  FormHtml::text('last_name', old('last_name'),
                                            array(
                                            'class'=>'form-control'
                                            ))  !!}
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            {!! FormHtml::label('email', trans("Attendee.email_address"), array('class'=>'control-label required')) !!}

                            {!!  FormHtml::text('email', old('email'),
                                                array(
                                                'class'=>'form-control'
                                                ))  !!}
                        </div>

                        <div class="form-group">
                            <div class="checkbox custom-checkbox">
                                <input type="checkbox" name="email_ticket" id="email_ticket" value="1" />
                                <label for="email_ticket">&nbsp;&nbsp;@lang("Attendee.send_invitation_n_ticket_to_attendee")</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- /end modal body-->
            <div class="modal-footer">
               {!! FormHtml::button(trans("basic.cancel"), ['class'=>"btn modal-close btn-danger",'data-dismiss'=>'modal']) !!}
               {!! FormHtml::submit(trans("ManageEvent.invite_attendee"), ['class'=>"btn btn-success"]) !!}
            </div>
        </div><!-- /end modal content-->
       {!! FormHtml::close() !!}
    </div>
</div>
