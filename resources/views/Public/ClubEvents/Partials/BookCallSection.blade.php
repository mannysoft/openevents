<!-- Public.ClubEvents.Partials.BookCallSection -->
<section id="section-member" class="section-member">
    <div class="container">
        <div class="section-title">
            <!-- <h1>Book Your Free Discovery Session Call!</h1> -->
        </div>
        <div class="row">
            <div class="col-lg-6 col-sm-12 align-self-center">
                @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="book_call_image" data-club-id="{{ $clubData->id }}" data-has-file-upload="true" data-file-upload-only="true" data-label="Book Call Image URL">
                    Edit Image
                </a>
                @endif
                <div class="embed-responsive embed-responsive-16by9">
                    @if (isset($clubData) && $clubData->book_call_image != '' && $clubData->book_call_image != null )
                    <img src="{{ $clubData->book_call_image }}" class="embed-responsive-item">
                    @else
                    @if(isset($bookCallimage))
                    <img class="embed-responsive-item" src="{{ $bookCallimage }}" />
                    @else
                    <img class="embed-responsive-item" src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/bookcall.png" />
                    @endif
                    @endif
                </div>
            </div>
            <div class="col-lg-6 col-sm-12 align-self-center">
                <div class="member-content">
                    @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                    <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="right_place_text" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->right_place_text }}" data-label="You Are In The Right Place section">
                        Edit
                    </a>
                    @endif
                    @if (isset($clubData) && $clubData->right_place_text != '' && $clubData->right_place_text != null )
                    {!! $clubData->right_place_text !!}
                    @else
                    <h1>You Are In The Right Place. At Global Woman You Will Find Your Power!</h1>
                    <br>
                    @endif
                    <!-- <p>Please follow the instructions to schedule the time and book a call with one of my coaches. Its quick and easy. Our time is GMT UK. My team will send you further details for the call.</p> -->
                    <a href="#section-ticket-book" target="_blank" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">Book My Ticket Now</a>
                </div>
            </div>
        </div>
    </div>
</section>