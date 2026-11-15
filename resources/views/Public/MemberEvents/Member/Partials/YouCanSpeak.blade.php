<section id="section-meeting" class="section-meeting">
    <div class="container">
        <div class="section-title">
            <h1></h1>
        </div>
        <div class="row">
            <div class="col-lg-6 col-sm-12 align-self-center">
                <img src="{{ $event->postImage }}" />
            </div>
            <div class="col-lg-6 col-sm-12 align-self-center">
                <div class="meeting-content">
                    @if(isset($extraDescription))
                    {{ $extraDescription }}
                    @else
                    <p>You can SPEAK to a global audience and gain greater visibility for you and your brand, business, project or mission, by being an influencer member of Global Woman Club. </p>
                    <p>We also have great online activities. Weekly masterclasses, online course and monthly online "Zoom" virtual networking meeting which gives you the opportunity to network with our members around the world. Just have your own food and drink with you for the meeting and come and enjoy a like minded global community of inspiring women.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>