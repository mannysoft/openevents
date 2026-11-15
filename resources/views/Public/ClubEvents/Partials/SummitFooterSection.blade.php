<!-- Public.ClubEvents.Partials.FooterSection -->
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
    <center><p class="text-white">{{ session('club_commission') }}</p></center>
</section>