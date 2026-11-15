<!-- Public.ClubEvents.Default.Partials.GalleryCardSection -->
<section id="section-galleryCard" class="section-galleryCard" style="background-color: @if(isset($globalmanclub) && $globalmanclub == true) #fff @endif;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="row no-gutters">
                        <div class="col-md-6 align-self-center">
                            @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                            <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="remember_join" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->remember_join }}" data-has-file-upload="true" data-label="Remember section">
                                Edit
                            </a>
                            @endif
                            @if (isset($clubData) && $clubData->remember_join_image != '' && $clubData->remember_join_image != null )
                            <img src="{{ $clubData->remember_join_image }}" class="card-img" alt="">
                            @else
                            <img src="https://davxegdvkjbks.cloudfront.net/club-images/625x441/london/1.jpg" class="card-img" alt="">
                            @endif
                        </div>
                        <div class="col-md-6 align-self-center">
                            <div class="card-body">
                                @if (isset($clubData) && $clubData->remember_join != '' && $clubData->remember_join != null )
                                {!! $clubData->remember_join !!}
                                @else
                                <h4 class="card-title">Remember, When You Join One Global Woman Club, You Become A Member Of All Global Woman Clubs &amp; The Growing Global Family Community.</h4>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="row no-gutters">
                        <div class="col-md-6 align-self-center">
                            @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                            <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="message_mirela" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->message_mirela }}" data-has-file-upload="true" data-label="Message fron Mirela section">
                                Edit
                            </a>
                            @endif
                            @if (isset($clubData) && $clubData->message_mirela_image != '' && $clubData->message_mirela_image != null )
                            <img src="{{ $clubData->message_mirela_image }}" class="card-img" alt="">
                            @else
                            <img src="https://davxegdvkjbks.cloudfront.net/club-images/625x441/london/2.jpg" class="card-img" alt="">
                            @endif
                        </div>
                        <div class="col-md-6 align-self-center">
                            <div class="card-body">
                                @if (isset($clubData) && $clubData->message_mirela != '' && $clubData->message_mirela != null )
                                {!! $clubData->message_mirela !!}
                                @else
                                <p class="card-text">In the last 8 years I have been travelling around the world and I have met thousands of women with a big desire to grow, expand and go global.</p>
                                <p class="card-text">Perhaps you have wanted to have launched your business earlier, or feel you are not prepared now. The good news is, that you are still in time to start building your personal brand, your Global Platform, and grow your confidence by being part of Global Woman and feel the power of the support group that you will find here.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>