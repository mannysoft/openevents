<!-- Public.ClubEvents.Partials.GallerySection -->
@if(isset($galleryImages))
<section id="section-gallery" class="section-gallery">
    <div class="container">
        <div class="row">
            @foreach ($galleryImages as $galleryImage)
            <div class="col-lg-4 col-md-6 col-sm-12">
                <img src="{{ $galleryImage }}" class="responsive-item">
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
@endif