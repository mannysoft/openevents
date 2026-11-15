<!-- Public.ClubEvents.Default.Partials.ClubInfoSection -->
<section id="section-clubInfo" class="section-clubInfo">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 col-sm-12 align-self-center">
        @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
        <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="club_info_section_video" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->club_info_section_video }}" data-has-video="true" data-video-only="true" data-label="Club Info section video">
            Edit
        </a>
        @endif
        
        <div class="embed-responsive embed-responsive-16by9">
        @if (isset($clubData) && $clubData->club_info_section_video != '' && $clubData->club_info_section_video != null )
        <iframe class="embed-responsive-item" src="{{ $clubData->club_info_section_video }}" allowfullscreen=""></iframe>
        @else
        <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/cT9KWhfNNtA?autoplay=0&modestbranding=1&controls=1&showinfo=0&rel=0&hd=1&wmode=transparent" allowfullscreen=""></iframe>
        @endif
        </div>
      </div>
      <div class="col-lg-6 col-sm-12 align-self-center">
        <div class="clubInfo-content">
          <h1>What Makes The Global Woman Club Networking Breakfast So Special?</h1>
          
          @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
        <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="club_info_section_first_text" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->club_info_section_first_text }}" data-label="Club Info section">
            Edit
        </a>
        @endif
        @if (isset($clubData) && $clubData->club_info_section_first_text != '' && $clubData->club_info_section_first_text != null )
        {!! $clubData->club_info_section_first_text !!}
        @else
            <p>It has evolved from Global Woman magazine, sharing so many great and inspirational stories and often bringing these women together to connect and share in person. Speak to anyone who has attended and they will say that it is unique and different to any other networking event they have been to. It has a warm, relaxed and supportive energy, and everyone is open and authentic.</p>
            <p>It is about building relationships and seeking mutually beneficial collaborations from a rich cultural diversity of women, very successful in what they do, but in such a broad range of professions and businesses.</p>
        @endif
        </div>
      </div>
      <div class="col-lg-12">
      @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
        <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="club_info_section_second_text" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->club_info_section_second_text }}" data-label="Club Info section">
            Edit
        </a>
        @endif
        @if (isset($clubData) && $clubData->club_info_section_second_text != '' && $clubData->club_info_section_second_text != null )
        {!! $clubData->club_info_section_second_text !!}
        @else
        <p>What they share in common is to serve a social and human good and purpose in whatever they do. For the entrepreneurs, it is to profit with a purpose. For career professionals, it is to succeed with a purpose. We often refer to it as the 'Life purpose'.</p>
        <p>It is the combination of all these magical ingredients that sprinkle a glowing sense of empowerment and happiness. United we are stronger and ready to face any challenges we are experiencing or may meet.</p>
        <p>Get a sense of the occasion by viewing a snapshot of the action and hear what the women have to say in the video here:</p>
        <p>Our aim is to connect professional and business women around the world, and we are celebrating together knowing that many of us may have come from different countries, different backgrounds and even being at different stages of life and our profession or business but we all have something in common: the drive and the passion to succeed in business or our chosen vocation.</p>
        <p>Global Woman Business Club helps women to build their confidence and belief that they can be successful in whatever they do. We help them build their brand and create awareness about their projects through our media platform.</p>
        <p>Non-members can attend once to try it out and decide if they want more of the Global Woman experience. Members can attend any physical club networking event in any location, or any virtual online networking meeting, and speak to the room or online group there.</p>
        <p>We do take pictures at events and share on Facebook (join us and see them at the Global Woman Club group: <a href="https://www.facebook.com/groups/554833658194282" target="_blank" class="text-red">https://www.facebook.com/groups/554833658194282</a>
 and on the Global Woman lobby; <a href="https://lobby.globalwomanclub.com/" target="_blank" class="text-red">https://lobby.globalwomanclub.com/</a> We will contact you about this and other events after you have booked your ticket. You may unsubscribe at any time. </p>
        @endif
      </div>
    </div>
  </div>
</section>
