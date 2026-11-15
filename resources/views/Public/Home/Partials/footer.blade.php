<section>
    <div class="container">

         <br>

        <div class="row">
            <img style="width: 100%;" src="https://globalwomanlobby.s3.eu-west-2.amazonaws.com/banners/partners-banner.webp">
        </div>

        <div class="row">
            <a href="https://globalwomanlounge.com/link" target="_blank">
                <img style="width: 100%;" src="https://globalwomanlobby.s3.eu-west-2.amazonaws.com/banners/app-banner.jpeg">
            </a>
        </div>
    </div>
</section>

@include('Public.ClubEvents.Partials.faq')

<section style="padding: 20px 0;">
    <div class="container" style="background-color: #da2926;">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <a href="https://director.globalwomanclub.com/apply-to-director-2025" target="_blank">
                    <img style="width: 100%;" src="https://globalwomanmagazine.com/wp-content/uploads/2025/02/regional-director-2025.webp">
                </a>
            </div>
        </div>
    </div>
</section>

<section id="section-footerTop" class="section-footerTop">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="footerTop-content">
                    <h3 class="text-white">Featured In Media:</h3>
                    <img src="{{ config('app.url') }}/assets/images/logos-sponsors.png" alt="">
                </div>
            </div>

            @if(!isset($hideFooter))
            <div class="col-lg-10">
                <div class="footerTop-content last-child">
                    <h3 class="text-white">Global Woman Enterprises</h3>
                    <img src="{{ config('app.url') }}/assets/images/logos-sponsors-1.png" alt="">
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<section id="section-footerBottom" class="section-footerBottom">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="footerBottom-content">
                    <p class="m-0">All Rights Reserved © | The Global Woman Club ® |
                        <script>
                            document.write(new Date().getFullYear())
                        </script>
                        {{ app_version() }}
                        <a href="https://globalwomanclub.com/terms-conditions" target="_blank" class="text-red">Terms Of Service</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>