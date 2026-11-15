@use('App\Services\FormHtml')
<div role="dialog"  class="modal fade " style="display: none;">
   {!! FormHtml::open(array('url' => route('postImportAttendee', array('event_id' => $event->id)),'files' => true, 'class' => 'ajax')) !!}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3 class="modal-title">
                    <i class="ico-user-plus"></i>
                    @lang("ManageEvent.invite_attendees")</h3>
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
						<!-- Import -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                {!! FormHtml::labelWithHelp('attendees_list', trans("ManageEvent.import_file"), array('class'=>'control-label required'),
                                    trans("ManageEvent.attendees_file_requirements")) !!}
                                {!!  FormHtml::styledFile('attendees_list',1,array('id'=>'input-attendees_list'))  !!}
                                </div>
                            </div>
                        </div>
						<div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="checkbox custom-checkbox">
                                        <input type="checkbox" name="email_ticket" id="email_ticket" value="1" />
                                        <label for="email_ticket">&nbsp;&nbsp;@lang("ManageEvent.send_invitation_n_ticket_to_attendees").</label>
                                    </div>
                                </div>
                            </div>
                		</div>
                    </div>
                </div>
            </div> <!-- /end modal body-->
            <div class="modal-footer">
               {!! FormHtml::button(trans("basic.cancel"), ['class'=>"btn modal-close btn-danger",'data-dismiss'=>'modal']) !!}
               {!! FormHtml::submit(trans("ManageEvent.create_attendees"), ['class'=>"btn btn-success"]) !!}
            </div>
        </div><!-- /end modal content-->
       {!! FormHtml::close() !!}
    </div>
</div>
