@use('App\Services\FormHtml')
<div role="dialog"  class="modal fade" style="display: none;">
   {!! FormHtml::open(array('url' => route('postCreateTicket', array('event_id' => $event->id)), 'class' => 'ajax')) !!}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3 class="modal-title">
                    <i class="ico-ticket"></i>
                    @lang("ManageEvent.create_ticket")</h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! FormHtml::label('title', trans("ManageEvent.ticket_title"), array('class'=>'control-label required')) !!}
                            {!!  FormHtml::text('title', old('title'),
                                        array(
                                        'class'=>'form-control',
                                        'placeholder'=>trans("ManageEvent.ticket_title_placeholder")
                                        ))  !!}
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! FormHtml::label('price', trans("ManageEvent.ticket_price"), array('class'=>'control-label required')) !!}
                                    {!!  FormHtml::text('price', old('price'),
                                                array(
                                                'class'=>'form-control',
                                                'placeholder'=>trans("ManageEvent.price_placeholder")
                                                ))  !!}


                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! FormHtml::label('quantity_available', trans("ManageEvent.quantity_available"), array('class'=>' control-label')) !!}
                                    {!!  FormHtml::text('quantity_available', old('quantity_available'),
                                                array(
                                                'class'=>'form-control',
                                                'placeholder'=>trans("ManageEvent.quantity_available_placeholder")
                                                )
                                                )  !!}
                                </div>
                            </div>

                        </div>

                        <div class="form-group more-options">
                            {!! FormHtml::label('description', trans("ManageEvent.ticket_description"), array('class'=>'control-label')) !!}
                            {!!  FormHtml::text('description', old('description'),
                                        array(
                                        'class'=>'form-control'
                                        ))  !!}
                        </div>

                        <div class="row more-options">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    {!! FormHtml::label('start_sale_date', trans("ManageEvent.start_sale_on"), array('class'=>' control-label')) !!}
                                    {!!  FormHtml::text('start_sale_date', old('start_sale_date'),
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
                                    {!!  FormHtml::label('end_sale_date', trans("ManageEvent.end_sale_on"),
                                                [
                                            'class'=>' control-label '
                                        ])  !!}
                                    {!!  FormHtml::text('end_sale_date', old('end_sale_date'),
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

                        <div class="row more-options">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! FormHtml::label('min_per_person', trans("ManageEvent.minimum_tickets_per_order"), array('class'=>' control-label')) !!}
                                    {!! FormHtml::selectRange('min_per_person', 1, 100, 1, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! FormHtml::label('max_per_person', trans("ManageEvent.maximum_tickets_per_order"), array('class'=>' control-label')) !!}
                                    {!! FormHtml::selectRange('max_per_person', 1, 100, 30, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="row more-options">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-checkbox">
                                        {!! FormHtml::checkbox('is_hidden', 1, false, ['id' => 'is_hidden']) !!}
                                        {!! FormHtml::label('is_hidden', trans("ManageEvent.hide_this_ticket"), array('class'=>' control-label')) !!}
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-12">
                        <a href="javascript:void(0);" class="show-more-options">
                            @lang("ManageEvent.more_options")
                        </a>
                    </div>

                </div>

            </div> <!-- /end modal body-->
            <div class="modal-footer">
               {!! FormHtml::button(trans("basic.cancel"), ['class'=>"btn modal-close btn-danger",'data-dismiss'=>'modal']) !!}
               {!! FormHtml::submit(trans("ManageEvent.create_ticket"), ['class'=>"btn btn-success"]) !!}
            </div>
        </div><!-- /end modal content-->
       {!! FormHtml::close() !!}
    </div>
</div>
