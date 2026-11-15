<!-- Public.ClubEvents.Default.Partials.AboutSection -->
<section id="section-about" class="section-about">
   <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="row no-gutters">
            
          @if (@$clubData->who_event_image_url != null && @$clubData->who_event_image_url != '')
            <div class="col-md-6 align-self-center">
                @if (isset($clubData) && $clubData->who_event_image_url != '' && $clubData->who_event_image_url != null )
                <img src="{{ $clubData->who_event_image_url }}" class="card-img" alt="">
                @else
                <img src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Paris/about+event.jpeg" class="card-img" alt="">
                @endif
            </div>
            @endif

            @if (@$clubData->who_event_image_url == null || @$clubData->who_event_image_url == '')
            <div class="col-md-12 align-self-center">
            @else
            <div class="col-md-6 align-self-center">
            @endif
              <div class="card-body">
                <h4 class="card-title">Who Is This Event For?</h4>
                @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="who_event" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->who_event }}" data-has-file-upload="true" data-label="Who is this Event for">
                    Edit
                </a>
                @endif
                @if (isset($clubData) && $clubData->who_event != '' && $clubData->who_event != null )
                {!! $clubData->who_event !!}
                @else
                <p>If you are a business owner, author, coach, mentor, consultant, trainer, practitioner, accountant, retails shop owner, or simply have an idea to start your business venture, or perhaps you want to take your knowledge and share it with the world - then this networking event is for you.</p>
                <p>If you are a woman working for a corporate but feel you need some inspiration and explore more personal development surrounding with successful conscious international women who genuinely support each other, then feel free to join us and experience a day full of joy and hope.</p>
                <p>This is the culture that we have create at our Global Woman Club – Empowering women Locally and Connecting them Globally.</p>
                <p>Contact support@globalwomanclub.com for any other queries.</p>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
