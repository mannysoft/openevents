<!-- Public.ClubEvents.Default.Partials.ClubGallerySection -->
<style>
    .deleteButton {
        opacity: 0;
        transition: opacity 0.5s ease;
        position: relative;
        /* padding: 0 40px; */
        height: 40px;
        line-height: 40px;
        max-width: 260px;
        cursor: pointer;
        z-index: 3;
    }

    .hoverme:hover .deleteButton {
        opacity: 1;
    }
</style>
<section id="section-clubGallery" class="section-clubGallery">
    <div class="container">
        <a id="section-club-gallery-images"></a>
        @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
        <a class="btn btn-light" data-toggle="modal" data-target="#editGalleryModal" data-target-section="club-gallery" data-club-id="{{ $clubData->id }}">
            Upload Images
        </a>
        <br />
        @endif

        <div class="row">
            @php
            if (isset($clubData->clubGalleryImages)) {
            $clubGalleryImages = $clubData->clubGalleryImages;
            }
            @endphp
            @foreach ($clubGalleryImages as $key => $galleryImage)
            <div class="col-lg-3 col-sm-6 hoverme">
                @if(is_object($galleryImage))
                <img src="{{ $galleryImage->image_url }}">
                @else
                <img src="{{ $galleryImage }}">
                @endif
                <!-- Show only on edit -->
                @if(request('edit_event') == 1)
                <svg data-toggle="modal" data-target="#deleteImageModal" data-image-id="{{ @$galleryImage->id }}" data-target-section="club-gallery" xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="#000000" class="bi bi-trash3 deleteButton" viewBox="0 0 16 16">
                    <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5" />
                </svg>
                @endif
            </div>
            @endforeach

        </div>
        <a href="https://globalwomanmagazine.com/privacy-policy-2" target="_blank" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">View Our Privacy Policy</a>
    </div>
</section>