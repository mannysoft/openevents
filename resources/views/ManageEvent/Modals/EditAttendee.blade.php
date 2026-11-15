@use('App\Services\FormHtml')
<div role="dialog"  class="modal fade" style="display: none;">
   {!! FormHtml::model($attendee, array('url' => route('postEditAttendee', array('event_id' => $event->id, 'attendee_id' => $attendee->id)), 'class' => 'ajax')) !!}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title">
                    <i class="ico-edit"></i>
                    {{ @trans("ManageEvent.edit_attendee_title", ["attendee"=> $attendee->full_name]) }}
                    </h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                   {!! FormHtml::label('ticket_id', trans("ManageEvent.ticket"), array('class'=>'control-label required')) !!}
                                   {!! FormHtml::select('ticket_id', $tickets, $attendee->ticket_id, ['class' => 'form-control']) !!}
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
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! FormHtml::label('email', trans("Attendee.email"), array('class'=>'control-label required')) !!}

                                    {!!  FormHtml::text('email', old('email'),
                                            array(
                                            'class'=>'form-control'
                                            ))  !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- /end modal body-->
            <div class="modal-footer">
               {!! FormHtml::hidden('attendee_id', $attendee->id) !!}
               {!! FormHtml::button(trans("basic.cancel"), ['class'=>"btn modal-close btn-danger",'data-dismiss'=>'modal']) !!}
               {!! FormHtml::submit(trans("ManageEvent.edit_attendee"), ['class'=>"btn btn-success"]) !!}
            </div>
        </div><!-- /end modal content-->
       {!! FormHtml::close() !!}
    </div>
</div>
