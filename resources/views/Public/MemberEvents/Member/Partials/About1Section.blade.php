<!-- Public.ClubEvents.Default.Partials.About1Section -->
<section id="section-about" class="section-about">
    <div class="container">
        <div class="section-title">
            <h1>Who Is This Event For?</h1>
        </div>
        <div class="row">
            <div class="col-lg-12">
            @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
            <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="who_event" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->who_event }}" data-label="Who is this Event for">
                Edit
            </a>
            @endif
            @if (isset($clubData) && $clubData->who_event != '' && $clubData->who_event != null )
            {!! $clubData->who_event !!}
            @else
            <p>These are special exclusive events! The capacity is limited so please BOOK your ticket early before we sell out, and don't miss the opportunity If you are a business owner, author, coach, mentor, consultant, trainer, practitioner, accountant, retail shop owner, or simply have an idea to start your business venture. Or perhaps you want to take your knowledge and share it with the world - then this networking event is for you.</p>
            <p>If you are a woman working for a corporate but feel you need some inspiration and want to explore more personal surrounding yourself with successful conscious international women who genuinely support each other, then feel free to join us and experience a day full of joy and hope.</p>
            <p>This is the culture that we have created at our Global Woman Club - Empowering women Locally and Connecting them Globally.</p>
            <p>Contact london@globalwomanclub.com for any other queries.</p>
            @endif
            </div>
        </div>

    </div>
</section>