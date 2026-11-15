<!-- Public.ClubEvents.Partials.QuoteSection -->
<section id="section-quote" class="section-quote">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">

                    @if(isset($globalmanclub) && $globalmanclub == true)
                    <h1>"Elevate Your Profile, Grow Holistically, Lead with Impact."</h1>
                    @else
                    <h1>"If You Want To Empower A Woman, Give Her A Microphone"</h1>
                    <span>- By Mirela Sula, Founder &amp; CEO Global Woman</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="container">
        {{-- <div class="row">
            <a href="https://www.globalwomansummit.com" target="_blank">
                <img style="width: 100%;" src="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/banner-summit.webp">
            </a>
        </div> --}}

        {{-- <div class="row" style="margin-top: 20px;margin-bottom: 20px;">
            <a href="https://course.globalwomanclub.com/home" target="_blank">
                <img style="width: 100%;" src="https://globalwomanmagazine.com/wp-content/uploads/2025/07/bootcamp.webp">
            </a>
        </div> --}}

        <!-- <div class="row">
            <a href="https://director.globalwomanclub.com/apply-to-director-2025" target="_blank">
                <img style="width: 100%;" src="https://globalwomanmagazine.com/wp-content/uploads/2025/02/regional-director-2025.webp">
            </a>
        </div> -->

        {{-- <div class="row" style="margin-top: 20px;margin-bottom: 20px;">
            <a href="https://subscription.globalwomanclub.com/studiovip-2025" target="_blank">
                <img style="width: 100%;" src="https://globalwomanmagazine.com/wp-content/uploads/2025/06/studio-training.webp">
            </a>
        </div> --}}
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

<style>
    .wrapper {
        width: 1280px;
        height: 139px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        overflow: hidden;
        border-radius: 15px;
    }

    .wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        /*--- selekton hover image ----*/
        background-color: #ededed;
        background: linear-gradient(100deg,
                rgba(255, 255, 255, 0) 40%,
                rgba(255, 255, 255, .5) 50%,
                rgba(255, 255, 255, 0) 60%) #ededed;
        background-size: 200% 100%;
        background-position-x: 180%;
        animation: 1s loading ease-in-out infinite;
    }

    @keyframes loading {
        to {
            background-position-x: -20%;
        }
    }
</style>

<script>
    let imageAnim = document.getElementById("image-animate");


    let imageArray = [
        //  "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/3.png",
        "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/summit+banner.jpeg"
        // "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/4.png",
        // "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/6.png",
        // "https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/7.png",

    ]

    let imageIndex = 0;

    // const startImage = () => {
    //     imageAnim.setAttribute("src", imageArray[imageIndex]);
    //     imageIndex++;
    //     if (imageIndex >= imageArray.length) {
    //         imageIndex = 0;
    //     }
    // }

    // setInterval(startImage, 800);
</script>