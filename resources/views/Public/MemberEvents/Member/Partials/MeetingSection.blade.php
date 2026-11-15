<!-- Public.ClubEvents.Default.Partials.MeetingSection -->
@include('Public.ClubEvents.Partials.videos')

<section id="section-meeting" class="section-meeting">
    <div class="container">
        <div class="section-title">
            <h1></h1>
        </div>
        <div class="row">
            <div class="col-lg-6 col-sm-12 align-self-center">
                @if (isset($clubData) && $clubData->meeting_section_image_url != '' && $clubData->meeting_section_image_url != null )
                <img src="{{ $clubData->meeting_section_image_url }}" class="card-img" alt="">
                @else
                <img src="{{ config('app.url') }}/assets/images/meeting.jpg">
                @endif
            </div>
            <div class="col-lg-6 col-sm-12 align-self-center">
                <div class="meeting-content">
                    @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                    <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="meeting_section" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->meeting_section }}" data-has-file-upload="true" data-label="Meeting section">
                        Edit
                    </a>
                    @endif
                    @if (isset($clubData) && $clubData->meeting_section != '' && $clubData->meeting_section != null )
                    {!! $clubData->meeting_section !!}
                    @else
                    <p>You can SPEAK to a global audience and gain greater visibility for you and your brand, business, project or mission, by being an influencer member of Global Woman Club. </p>
                    <p>We also have great online activities. Weekly masterclasses, online course and monthly online "Zoom" virtual networking meeting which gives you the opportunity to network with our members around the world. Just have your own food and drink with you for the meeting and come and enjoy a like minded global community of inspiring women.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>