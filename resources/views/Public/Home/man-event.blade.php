<div class="col-lg-4 col-sm-6">
    <div class="grid-widget" style="height: 340px">
        <div class="widget-header">
            <a href="{{ $event['is_past'] ? $event['video_recording_url']: $event['event_url'] }}" target="_blank" class="opacity">
                <img width="370" height="150" src="{{ $event['event_banner'] }}" alt="">
            </a>
        </div>
        <div class="widget-body">
            <p class="date">
                <span class="float-left"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{ $event['start_date'] }}</span>
                <span class="float-right"><i class="fa fa-clock" aria-hidden="true"></i> {{ $event['start_time'] }}</span>
            </p>
            <center><h3>{{$event['title']}}</h3></center>
            <div style="width:100%;display:inline-block;border-top: 1px solid #ddd;margin:16px 0 0;">
                <div class="float-left">
                    <p id="countdownwebinarall" style="border:0;margin:0 !important;" data-countdown="{{ $event['start_date'] }}">{{ date('Y/m/d', strtotime($event['start_date'])) }}</p>
                </div>
        
            </div>

            <div class="pull-right">
                <a style="background-color: #c5da2a; padding: 8px 20px;    border-radius: 2px;    text-align: center;" href="{{ $event['event_url'] }}" target="_blank" class="btn bg-red text-black">REGISTER HERE</a>
            </div>
        </div>
    </div>
</div>