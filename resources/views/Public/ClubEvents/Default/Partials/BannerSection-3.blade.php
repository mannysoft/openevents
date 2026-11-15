<!-- Public.ClubEvents.Default.Partials.BannerSection-3 -->
<section id="section-banner" class="section-banner">
    @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
    <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="banner_section_3" data-club-id="{{ $clubData->id }}" data-has-file-upload="true" data-file-upload-only="true" data-label="Upload File">
        Edit Image
    </a>
    @endif
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if (isset($clubData) && @$clubData->banner_section_3 != '' && @$clubData->banner_section_3 != null )
                <img src="{{ @$clubData->banner_section_3 }}" class="">
                @else
                {{-- <img src="https://images.clickfunnels.com/cb/ade50d3cf040bab4f286c50b9d8643/WhatsApp-Image-2022-01-27-at-13.26.23.jpg" alt=""> --}}
                @endif
            </div>
        </div>
    </div>
</section>