@use('App\Services\FormHtml')
<div role="dialog" class="modal fade" style="display: block;">
    {!! FormHtml::open(['url' => route('postCreateEventAccessCode', ['event_id' => $event->id]), 'class' => 'ajax']) !!}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3 class="modal-title">
                    <i class="ico-ticket"></i>
                    @lang("AccessCodes.create_access_code")</h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! FormHtml::label('code', trans("AccessCodes.access_code_title"), ['class'=>'control-label required']) !!}
                            {!! FormHtml::text('code', old('code'),
                                        [
                                            'class'=>'form-control',
                                            'placeholder' => trans("AccessCodes.access_code_title_placeholder")
                                        ])  !!}
                        </div>
                    </div>
                </div>
            </div> <!-- /end modal body-->
            <div class="modal-footer">
                {!! FormHtml::button(trans("basic.cancel"), ['class'=>"btn modal-close btn-danger",'data-dismiss'=>'modal']) !!}
                {!! FormHtml::submit(trans("AccessCodes.create_access_code"), ['class'=>"btn btn-success"]) !!}
            </div>
        </div><!-- /end modal content-->
        {!! FormHtml::close() !!}
    </div>
</div>
