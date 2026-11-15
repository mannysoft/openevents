<div class="col-lg-4 col-sm-6">
    <div class="grid-widget" style="height: 340px">
        <div class="widget-header">
            <a href="{{ $event['is_past'] ? $event['video_recording_url']: $event['masterclass_url'] }}" target="_blank" class="opacity">
                <img width="370" height="150" src="{{ $event['masterclass_banner'] }}" alt="">
            </a>
        </div>
        <div class="widget-body">
            <p class="date">
                <span class="float-left"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{ $event['start_date'] }}</span>
                <span class="float-right"><i class="fa fa-clock" aria-hidden="true"></i> {{ $event['start_time'] }}</span>
            </p>
            <h3>{{$event['title']}}</h3>
            <div style="width:100%;display:inline-block;border-top: 1px solid #ddd;margin:16px 0 0;">
                <div class="float-left">
                    @if($event['is_past'] === true)
                    <p id="countdownwebinarall" style="border:0;margin:0 !important;" data-countdown="{{ $event['start_date'] }}">{{ date('Y/m/d', strtotime($event['start_date'])) }}</p>
                    @else
                    <p id="countdownwebinarall" style="border:0;margin:0 !important;" data-countdown="{{ $event['start_date'] }}">{{ date('Y/m/d', strtotime($event['start_date'])) }}</p>
                    @endif
                </div>
        
            </div>

            <div class="pull-right">
                @if($event['is_past'] === true)
                <a style="background-color: #dc273f;    padding: 8px 20px;    border-radius: 2px;    text-align: center;" href="{{ $event['video_recording_url'] }}" class="btn bg-red text-white">VIEW RECORDING</a>
                @else
                <a style="background-color: #dc273f;    padding: 8px 20px;    border-radius: 2px;    text-align: center;" href="{{ $event['masterclass_url'] }}" class="btn bg-red text-white">REGISTER NOW TO JOIN</a>
                @endif
            </div>
        </div>
    </div>
</div>