<!-- Public.ClubEvents.Default.Partials.GlobalManSections -->
<section id="section-two" class="section-two" style="background-color: #eee;">
    <div class="container">

        <div class="row">
            <div class="col-lg-12">
                <div class="row no-gutters">
                    <div class="col-md-6 align-self-center">
                        @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                        <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="showup" data-club-id="{{ $clubData->id }}" data-content="{{ @$clubData?->showup }}" data-has-file-upload="true" data-label="Remember section">
                            Edit
                        </a>
                        @endif
                        @if (isset($clubData) && @$clubData->showup_image != '' && @$clubData->showup_image != null )
                        <img src="{{ $clubData->showup_image }}" class="card-img" alt="">
                        @else
                        <img src="https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65bbbc8182a5e366499c46b2.webp" class="card-img" alt="">
                        @endif
                    </div>
                    <div class="col-md-6 align-self-center">
                        <div class="card-body">
                            @if (isset($clubData) && @$clubData->showup != '' && @$clubData->showup != null )
                            {!! $clubData?->showup !!}
                            @else
                            <p class="card-title">If you are simply looking to survive then this is not the club for you.If you are pushing for success then we would like to challenge you. Challenge you to look at what that means for you. Is it simply success in business or do you want to succeed in all areas of your life; not just business and perhaps wealth but in your relationships, your health and, most importantly, yourself.</p>
                            @endif
                        </div>
                    </div>
                </div>
                
            </div>
        </div>


    </div>
</section>


<!-- Public.ClubEvents.Partials.QuoteSectionTwo -->
<section id="section-quoteTwo" class="section-quoteTwo" style="background-color: rgba(136, 136, 136, 0.33)">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">

                    @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
                    <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="quote_text" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->quote_text }}" data-label="About Event">
                        Edit
                    </a>
                    @endif
            
                    @if (isset($clubData) && $clubData->quote_text != '' && $clubData->quote_text != null )
                    {!! $clubData->quote_text !!}
                    @else
                     <p>As Johann Hari would say...</p>
                     <p>"Men's groups provide the social medicine men need because all they need is connection."
 
                         “The opposite of addiction is not sobriety; the opposite of addiction is connection."</p>
 
                     <p>You are trying hard! You studied hard, got a degree, found a job, built a family, bought your dream house and non stop working to provide and validate your worth, as a hero for your wife and your children. I know sometimes you may have a feeling you may lose it all one day. And that feeling is becoming a virus inside you.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

@include('Public.ClubEvents.Default.Partials.GalleryCardSection')
@include('Public.ClubEvents.Default.Partials.ClubGallerySection', ['clubGalleryImages' => [
'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events%20Images/EwUSfT68UxdBGpur41Waab2Q2VbjhyqiBRpt1bxb.webp',
]
])

{{-- <section id="section-hostInfo" class="section-hostInfo p-0">
    <div class="row m-0">
        <div class="col-lg-6 col-sm-12 bg" style="background-image: url('https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65bbc444e767b46e2ff0daa6.jpeg');">
            <img src="https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65bbc444e767b46e2ff0daa6.jpeg" alt="">
        </div>
        <div class="col-lg-6 col-sm-12 p-0 align-self-center">
            <div class="hostInfo-content">

                <h1>Our Story</h1>
                <p>In the last 10 years we have been on a journey with Global Woman Club creating a community of empowered women around the world. At the beginning we started with the intention to provide a safe space mainly for women, but soon after we realised that men showed interest in joining us. When we launched the Global Woman magazine, we thought only women would appear in the front cover, but men started inquiring how to be part of this media platform. That's why one year later we launched Global Man Magazine. As we kept growing the club in different countries of the world, in each club we would see men signing up and joining us. But obviously the attention would go mainly to women, and somehow they would feel they were invading the “women’s safe space” and some of them would say “I feel like a fish without water”.</p>
            </div>
        </div>
    </div>
</section> --}}

<section id="section-two" class="section-two" style="background-color: #eee;">
    <div class="container">
        <div class="section-title" style="margin-bottom: 70px;">
            @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1)
            <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="globalman_exists_text" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->globalman_exists_text }}" data-label="About Event">
                Edit
            </a>
            @endif
    
            @if (isset($clubData) && $clubData->globalman_exists_text != '' && $clubData->quote_text != null )
            {!! $clubData->globalman_exists_text !!}
            @else
            <h1>Globalman exists to inspire men to thrive in all areas of life—personal growth, health, meaningful relationships, wealth creation, and business success. Co-founded by CEO Ian Speirs and Nick Feeney (Culture and Wellbeing Director), Globalman is built on a shared commitment to holistic success and gender equality on a global platform</h1>
            @endif
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="row no-gutters">
                    
                    <div class="col-md-6 align-self-center">
                        <div class="card-body">
                            @if (isset($clubData) && @$clubData->global_woman_partnership != '' && @$clubData->global_woman_partnership != null )
                            {!! $clubData?->global_woman_partnership !!}
                            @else
                            <p>In partnership with Globalwomen, we champion collaboration between men and women, fostering understanding and mutual growth. By connecting men with a global network of driven peers and forward-thinking women, Globalman inspires men to lead with purpose, vulnerability, and impact. We aim to create a supportive space where men can grow in every facet of life, transforming themselves and those around them.
                                Both Ian and Nick are partnered with two incredible women who embody the values of Globalwomen, reinforcing our commitment to fostering an inclusive environment where men and women thrive together.</p>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6 align-self-center">
                        @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                        <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="global_woman_partnership" data-club-id="{{ $clubData->id }}" data-content="{{ @$clubData?->global_woman_partnership }}" data-has-file-upload="true" data-label="Remember section">
                            Edit
                        </a>
                        @endif
                        @if (isset($clubData) && @$clubData->global_woman_partnership_image != '' && @$clubData->global_woman_partnership_image != null )
                        <img src="{{ $clubData->global_woman_partnership_image }}" class="card-img" alt="">
                        @else
                        <img class="rounded img-fluid" src="https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65bbc4a0748da72979d6d94a.jpeg" class="card-img" alt="">
                        @endif
                    </div>
                </div>
                
            </div>
        </div>

    </div>
</section>

{{-- <section id="section-hostInfo" class="section-hostInfo p-0">
    <div class="row m-0">
        <div class="col-lg-4 col-sm-12 bg" style="background-image: url('https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65da0f53b7e5d280098bd464.jpeg');">
            <img class="rounded img-fluid" src="https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65da0f53b7e5d280098bd464.jpeg">
        </div>
        <div class="col-lg-8 col-sm-12 p-0 align-self-center">
            <div class="hostInfo-content">
                <p>This club will offer emotional support and practical advice about a challenge, concern, or may be facing. These events will help men to gain more insight into their lives as bachelors, husbands, and fathers. We will meet to learn how to better themselves in life as individuals. We will cover all aspects that touch on a man’s life: such as personal growth, spiritual, marital or mental health related. This is done by awareness creation, educating, volunteering, and advocacy.</p>
                <p>You may have “The nice guy syndrome” or feeling under the pressure to meet the standards of the new world. There are new social skills that we need to learn every day. We help you on this journey, to overcome issues like low career stagnation, self-esteem, shyness, loneliness, and anger, which may back you from living the life you deserve.</p>
                <p>This club will always celebrate diversity and give the members different views on different issues, providing a platform to learn the best ways to approach issues they are facing. We want to create a platform where men have a better chance of overcoming the issues they face without sinking into depression or self-harm.</p>
                <p><a href="#" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">Book your place NOW</a></p>
            </div>
        </div>
    </div>
</section>


<section id="section-two" class="section-two" style="background-color: #eee;">
    <div class="container">
        <div class="section-title" style="margin-bottom: 70px;">
        </div>
        <div class="row">
            <div class="col-lg-7 col-sm-12 align-self-center">
                <div class="two-content">

                    <ul style="padding: 0 0 0 16px;margin: 0;">
                        <li style="margin-bottom: 15px;">We also want to upgrade your life to the level where you feel you belong, you feel inspired to contribute, be a great role model for your family and friends and co-create with others (including women) for a better world. A new version of the world where men and women show up in the best version of themselves tapping into both feminine and masculine energy as a perfect universal balance.</li>
                    </ul>
                    <a href="#" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">Book your place NOW</a>

                </div>
            </div>
            <div class="col-lg-5 col-sm-12 align-self-center">
                <div class="two-content">
                    <tr>
                        <td align="left" style="font-family:'Roboto',sans-serif;font-size:16px;color:#333333;font-weight:500">
                            <img class="rounded img-fluid" src="https://images.leadconnectorhq.com/image/f_webp/q_80/r_1200/u_https://assets.cdn.filesafe.space/ZDavdc0F1sm0KU0x8ggQ/media/65da0f9af2b319740f94704c.jpeg" />
                        </td>
                    </tr>
                    <tr>
                        <td height="30"> </td>
                    </tr>
                    <tr>

                        <td align="left" style="font-family:'Roboto',sans-serif;font-size:16px;color:#333333;font-weight:500">
                            <br><br><br>
                        </td>
                    </tr>
                </div>
            </div>
        </div>

    </div>
</section>

<section id="section-clubInfo" class="section-clubInfo">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-sm-12 align-self-center">
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/x6a0IxjW6_g?si=YPfIudWGKlkQTh7E" allowfullscreen=""></iframe>
                </div>
            </div>
            <div class="col-lg-6 col-sm-12 align-self-center">
                <div class="clubInfo-content">
                    <h1>Don’t wait for that moment. You can turn everything around.</h1>
                    <p>Come and join Global Man Club to explore who you really are, beyond your job, beyond your commitment as a provider, beyond the worry to make more money, and build more muscles, beyond the fear trying to fill an emotional hole with “dangerous” choices such as drink, drugs, porn or unhealthy relationships as a way of coping.</p>
                </div>
            </div>
        </div>
    </div>
</section> --}}