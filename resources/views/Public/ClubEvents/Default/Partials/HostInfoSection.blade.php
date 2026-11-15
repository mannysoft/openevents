<!-- Public.ClubEvents.Default.Partials.HostInfoSection -->
<section id="section-meeting" class="section-hostInfo pr-10 pl-10">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-sm-12 bg" style="background-image: url('{{ @$clubData->director_image }}');">
                @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
                <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="director_image" data-club-id="{{ $clubData->id }}" data-has-file-upload="true" data-file-upload-only="true" data-label="Update Director Image">
                    Edit Image
                </a>
                @endif
                <img src="{{ @$clubData->director_image }}" alt="">
            </div>
            <div class="col-lg-6 col-sm-12 p-0 align-self-center">
                <div class="hostInfo-content">
                    @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
                    <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="about_regional_director" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->about_regional_director }}" data-label="about regional director">
                        Edit
                    </a>
                    <br />
                    @endif
                    @if (isset($clubData) && $clubData->about_regional_director != '' && $clubData->about_regional_director != null )
                    {!! $clubData->about_regional_director !!}
                    @else
                    <!-- <h1>Places Are Limited And Advance Booking Is Essential</h1> -->
                    <p>With a speaker ticket, you can speak to the online global audience for two minutes, at one meeting. (Global Woman Club premium members can speak at any meeting, as often as they choose. spaces permitting). You can share your personal story, your mission and/or vision, and talk about your business.</p>
                    <p>Mirela, your host, will also be showing the benefts of membership, with the new Global Woman Club app and how to make best use of it, and the Global Woman Academy, a content rich online learning resource. Now is the perfect time to make best use of these resources, to stay connected, the brain engaged, and be ready to hit the ground running when life beyond the coronravirus Covid-19 returns to some kind of "normality", which may become the "new normal".</p>
                    <p class="m-0">Premium members can attend any physical or virtual networking meeting of any club in a one year period,
                        and speak there. We have Global Woman Clubs in London, Paris, Amsterdam, Cyprus, Tirana (Albania) Frankfurt, Stockholm, Zurich, North Macedonia,
                        Los Angeles, New York, Chicago, Johannesburg, Manila, and Mongolia.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>