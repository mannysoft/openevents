<!-- Public.ClubEvents.Default.Partials.GallerySection -->
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
<section id="section-gallery" class="section-gallery @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @endif">
    <div class="container">
        <a id="section-gallery-images"></a>
        @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
        <a class="btn btn-light" data-toggle="modal" data-target="#editGalleryModal" data-target-section="gallery" data-club-id="{{ $clubData->id }}">
            Upload Images
        </a>
        <br />
        @endif
        <div class="row">
            @php
            if (isset($clubData->galleryImages)) {
            $galleryImages = $clubData->galleryImages;
            }
            @endphp
            @foreach ($galleryImages as $key => $galleryImage)
            <div class="col-lg-4 col-md-6 col-sm-12 hoverme">
                @if(is_object($galleryImage))
                <img src="{{ $galleryImage->image_url }}" class="responsive-item">
                @else
                <img src="{{ $galleryImage }}" class="responsive-item">
                @endif
                <!-- <button type="button" class="deleteButton">
                    Edit
                </button> -->
                <!-- Show only on edit -->
                @if(request('edit_event') == 1)
                <svg data-toggle="modal" data-target="#deleteImageModal" data-image-id="{{ @$galleryImage->id }}" xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="#ffffff" class="bi bi-trash3 deleteButton" viewBox="0 0 16 16">
                    <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5" />
                </svg>
                @endif
            </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="gallery-content">
                    <h1 class="text-white">Don’t Miss This Life-Shifting Opportunity!</h1>
                    <p class="text-white">Hurry up! Places are limited.</p>
                    <a href="#section-ticket-book" class="btn bg-black text-white">YES! I want to be part of it.</a>
                </div>
            </div>
        </div>
    </div>
</section>