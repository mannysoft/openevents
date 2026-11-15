<!DOCTYPE html>
<html lang="en">

<head>
    <title>Global Woman Events</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0" />
    <!-- bootstrap -->
    <link rel="stylesheet" href="assets/stylesheet/bootstrap.min.css" />
    <!-- swiper -->
    <link rel="stylesheet" href="assets/javascript/swiper/swiper-bundle.min.css" />
    <!-- fonts -->
    <link rel="stylesheet" href="assets/fonts/fontawesome/all.css" />
    <!-- custom -->
    <link rel="stylesheet" href="assets/stylesheet/landing.css" />
</head>

{{-- <link rel="stylesheet" href="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/gw-header/t-header.css">
<link rel="stylesheet" href="https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/gw-header/magazine.css"> --}}

{{-- <div id="gw-header"></div> --}}
<body>
    <script type="text/javascript" src="https://secure.rate8deny.com/js/218880.js"></script>
    @include('Public.Home.Partials.header')

    @include('Public.Partials.banner', ['addLine' => true])

    <section id="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center">
                        <h2><span>Global Woman Club Events</h2>
                        <p>Upcoming events</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <!-- club-meeting -->
                    <section id="club-meeting" class="p-0">
                        <div class="row">
                            <?php $count = 0; ?>
                            @foreach($eventsData as $event)
                            @php
                            if ($event['isMember'] == '1' || $event['getId'] == '') {
                            continue;
                            }
                            $newDate = date("D, d M Y", strtotime($event['start_date']));
                            $newTime = date("h:i A", strtotime($event['start_time']));
                            $eventUrl = 'club/'.$event['getId']; //$event['eventurl'];
                            // $eventUrl = 'club/'.$event['eventClub'] . '-' .$event['start_date'];
                            if($event['timezone']){
                            $dt1 = new DateTime($event['start_date'], new DateTimeZone($event['timezone']));
                            $tz1 = new DateTimeZone('Asia/Kolkata'); // or whatever zone you're after
                            $dt1->setTimezone($tz1);
                            $st1 = $dt1->format('Y/m/d H:i:s');}
                            else{
                            $dt1 = new DateTime($event['start_date'], new DateTimeZone('Asia/Kolkata'));
                            $tz1 = new DateTimeZone('Asia/Kolkata'); // or whatever zone you're after
                            $dt1->setTimezone($tz1);
                            $st1 = $dt1->format('Y/m/d H:i:s');
                            }
                            //if($count == 6) break;
                            @endphp
                            @if($event['webinar']=="0")
                            <div class="col-lg-3 col-sm-6">
                                <div class="grid-widget">
                                    <div class="widget-header">
                                        <a href="{{$eventUrl}}" target="_blank" class="opacity">
                                            <img width="370" height="150" src="{{ $event['postImage'] }}" alt="">
                                        </a>
                                    </div>
                                    <div class="widget-body">
                                        <p class="date">
                                            <span class="float-left"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{$newDate}}</span>
                                            <span class="float-right"><i class="fa fa-clock" aria-hidden="true"></i> {{$newTime}}</span>
                                        </p>
                                        <h3>{{$event['postTitle']}}</h3>
                                        <div style="width:100%;display:inline-block;border-top: 1px solid #ddd;margin:16px 0 0;">
                                            <div class="float-left">
                                                <p id="countdownwebinarall" style="border:0;margin:0 !important;" data-countdown="{{ date('Y/m/d H:i:s', strtotime($st1)) }}">{{ date('Y/m/d', strtotime($event['start_date'])) }}</p>
                                            </div>
                                            <div class="float-right">
                                                <div style="padding-top:8px;color:#818181;">{{$event['event_ttype']}}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $count++; ?>
                            @endif
                            @endforeach
                        </div>
                    </section>
                    
                </div>
            </div>
        </div>
    </section>

    {{-- <section id="club-meeting">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center">
                        <h2><span>Global Woman Club Events</h2>
                        <p>Upcoming events</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="swiper-container evnt-container">
                        <div class="swiper-wrapper">
                            <?php $count = 0; ?>
                            @foreach($eventsData as $event)
                            @php
                            if ($event['isMember'] == '1' || $event['getId'] == '') {
                            continue;
                            }
                            $newDate = date("D, d M Y", strtotime($event['start']));
                            $newTime = date("h:i A", strtotime($event['start']));
                            $eventUrl = 'club/'.$event['getId'];
                            // $eventUrl = 'club/'.$event['eventClub'] . '-' .$event['start_date'];
                            @endphp
                            <div class="swiper-slide">
                                <div class="col-lg-12 col-sm-6">
                                    <div class="grid-widget" style="height: 335px;">
                                        <div class="widget-header">
                                            <a href="{{$eventUrl}}" target="_blank" class="opacity">
                                                <img width="370" height="150" src="{{ $event['postImage'] }}" alt="">
                                            </a>
                                        </div>
                                        <div class="widget-body">
                                            <h3>{{$event['postTitle']}}</h3>
                                            <p style="color:#818181;">{{$event['event_ttype']}}</p>
                                            <p class="date">
                                                <span class="float-left"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{$newDate}}</span>
                                                <span class="float-right"><i class="fa fa-clock" aria-hidden="true"></i> {{$newTime}}</span>
                                            </p>
                                            <a style="background-color: #dc273f;    padding: 8px 20px;    border-radius: 2px;    text-align: center;" href="{{$eventUrl}}" class="btn bg-red text-white">Book Now</a>
                                            <div style="width:100%;display:inline-block;border-top: 1px solid #ddd;margin:16px 0 0;">
                                                <div class="float-right">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
                        <!-- Add Arrows -->
                        <div class="swiper-button-next">
                            <i class="fa fa-chevron-right"></i>
                        </div>
                        <div class="swiper-button-prev">
                            <i class="fa fa-chevron-left"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}

    <section>
        <div class="container">

            <div class="row">
                <a href="https://globalwomanlounge.com/link" target="_blank">
                    <img style="width: 100%;" src="https://globalwomanlobby.s3.eu-west-2.amazonaws.com/banners/app-banner.jpeg">
                </a>
            </div>

             {{-- <div class="row">
                <a href="https://mirela.globalwomanevents.com/challenge" target="_blank">
                    <img style="width: 100%;" src="https://globalwomanmagazine.com/wp-content/uploads/2025/12/banner-challenge.webp">
                </a>
            </div>

            <br> --}}

            {{-- <div class="row">
                <a href="https://course.globalwomanclub.com/home" target="_blank">
                    <img style="width: 100%;" src="https://globalwomanmagazine.com/wp-content/uploads/2025/07/whatsapp-image-2025-07-15-at-8-35-51-pm-1536x279.webp">
                </a>
            </div> --}}
{{-- 
            <br>

            <div class="row">
                <a href="https://subscription.globalwomanclub.com/studiovip-2025" target="_blank">
                    <img style="width: 100%;" src="https://globalwomanmagazine.com/wp-content/uploads/2025/06/studio-training.webp">
                </a>
            </div> --}}

        </div>
    </section>

    <!-- wrapper -->
    <section id="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="section-title">
                        <h2>Club Meetings <a href="{{ config('app.url') }}/clubs" class="float-right text-red">View All <i class="fa fa-chevron-right"></i></a></h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <!-- club-meeting -->
                    <section id="club-meeting" class="p-0">
                        <div class="row">
                            <?php $count = 0; ?>
                            @foreach($eventsData as $event)
                            @php
                            if ($event['isMember'] == '1' || $event['getId'] == '') {
                            continue;
                            }
                            $newDate = date("D, d M Y", strtotime($event['start']));
                            $newTime = date("h:i A", strtotime($event['start']));
                            $eventUrl = 'club/'.$event['getId']; //$event['eventurl'];
                            // $eventUrl = 'club/'.$event['eventClub'] . '-' .$event['start_date'];
                            if($event['timezone']){
                            $dt1 = new DateTime($event['start'], new DateTimeZone($event['timezone']));
                            $tz1 = new DateTimeZone('Asia/Kolkata'); // or whatever zone you're after
                            $dt1->setTimezone($tz1);
                            $st1 = $dt1->format('Y/m/d H:i:s');}
                            else{
                            $dt1 = new DateTime($event['start'], new DateTimeZone('Asia/Kolkata'));
                            $tz1 = new DateTimeZone('Asia/Kolkata'); // or whatever zone you're after
                            $dt1->setTimezone($tz1);
                            $st1 = $dt1->format('Y/m/d H:i:s');
                            }
                            if($count == 6) break;
                            @endphp
                            @if($event['webinar']=="0")
                            <div class="col-lg-4 col-sm-6">
                                <div class="grid-widget">
                                    <div class="widget-header">
                                        <a href="{{$eventUrl}}" target="_blank" class="opacity">
                                            <img width="370" height="150" src="{{ $event['postImage'] }}" alt="">
                                        </a>
                                    </div>
                                    <div class="widget-body">
                                        <p class="date">
                                            <span class="float-left"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{$newDate}}</span>
                                            <span class="float-right"><i class="fa fa-clock" aria-hidden="true"></i> {{$newTime}}</span>
                                        </p>
                                        <h3>{{$event['postTitle']}}</h3>
                                        <div style="width:100%;display:inline-block;border-top: 1px solid #ddd;margin:16px 0 0;">
                                            <div class="float-left">
                                                <p id="countdownwebinarall" style="border:0;margin:0 !important;" data-countdown="{{ date('Y/m/d H:i:s', strtotime($st1)) }}">{{ date('Y/m/d', strtotime($event['start'])) }}</p>
                                            </div>
                                            <div class="float-right">
                                                <div style="padding-top:8px;color:#818181;">{{$event['event_ttype']}}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $count++; ?>
                            @endif
                            @endforeach
                        </div>
                    </section>
                    <!-- mastermind -->
                    <section id="mastermind" class="p-0">
                        <!-- <div class="row">
                            <div class="col-lg-12">
                                <div class="section-title">
                                    <h2>Mastermind <a href="{{ config('app.url') }}/events" class="float-right text-red">View All <i class="fa fa-chevron-right"></i></a></h2>
                                </div>
                            </div>
                        </div> -->
                        @foreach($eventsData as $event)
                        @if($event['webinar']=="1")
                        @if($event['postTitle']!="")
                        @endif
                        @endif
                        @endforeach
                        <div class="row">
                            @foreach($eventsData as $event)
                            @if($event['webinar']=="1")
                            @php
                            $newDate = date("D, d M Y", strtotime($event['start']));
                            $newTime = date("h:i A", strtotime($event['start']));
                            $dt1 = new DateTime($event['start'], new DateTimeZone($event['timezone'] ?: 'Asia/Kolkata'));
                            $tz1 = new DateTimeZone('Asia/Kolkata'); // or whatever zone you're after
                            $dt1->setTimezone($tz1);
                            $st1 = $dt1->format('Y/m/d H:i:s');
                            @endphp
                            <div class="col-lg-4 col-sm-6">
                                <div class="grid-widget">
                                    <div class="widget-header">
                                        <a href="{{$event['eventurl']}}" class="opacity" target="_blank">
                                            <img width="370" height="150" src="{{ $event['postImage'] }}" alt="">
                                        </a>
                                    </div>
                                    <div class="widget-body">
                                        <p class="date">
                                            <span class="float-left"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{$newDate}}</span>
                                            <span class="float-right"><i class="fa fa-clock" aria-hidden="true"></i> {{$newTime}}</span>
                                        </p>
                                        <h3>{{$event['postTitle']}}</h3>
                                        <div style="width:100%;display:inline-block;border-top: 1px solid #ddd;margin:16px 0 0;">
                                            <div class="float-left">
                                                <p id="countdownwebinar" style="border:0;margin:0 !important;" data-countdown="{{ date('Y/m/d H:i:s', strtotime($st1)) }}">{{ date('Y/m/d', strtotime($event['start'])) }}</p>
                                            </div>
                                            <div class="float-right">
                                                <div style="padding-top:8px;color:#818181;">{{$event['event_ttype']}}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </section>
                </div>
                {{-- <div class="col-lg-4 b-left b-right">
                    <div class="advertisement">
                        <div class="adv">
                            <a href="https://mastermind.thebusinesswoman.today/webinar-registration36525698" target="_blank">
                                <img src="https://globalwomanmagazine.com/wp-content/uploads/2020/07/4.jpg" alt="">
                            </a>
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>
    </section>

    <section id="global-man-events">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="section-title">
                        <h2>Upcoming Global Man Events</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <!-- club-meeting -->
                    <section id="club-meeting" class="p-0">
                        <div class="row">
                            @foreach($globalManEvents as $event)
                            
                            @include('Public.Home.man-event', ['event' => $event])
                            @endforeach
                        </div>
                    </section>

                </div>
            </div>
        </div>

    </section>

    <section id="memberclass">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="section-title">
                        <h2>Upcoming Masterclass</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <!-- club-meeting -->
                    <section id="club-meeting" class="p-0">
                        <div class="row">
                            @foreach($masterclassesPresent as $event)
                            
                            @include('Public.Home.event', ['event' => $event])
                            @endforeach
                        </div>
                    </section>

                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="section-title">
                        <h2>Past Masterclass</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <!-- club-meeting -->
                    <section id="club-meeting" class="p-0">
                        <div class="row">
                            @foreach($masterclasses as $event)
                            
                            @include('Public.Home.event', ['event' => $event])
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>
        </div>

    </section>

    <section id="club-meeting">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center">
                        <h2><span>Member Events</h2>
                        <p>Upcoming events</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="swiper-container evnt-container">
                        <div class="swiper-wrapper">
                            <?php $count = 0; ?>
                            @foreach($eventsData as $event)
                            @php
                            if ($event['isMember'] != '1' && $event['getId'] != '') {
                            continue;
                            }
                            $newDate = date("D, d M Y", strtotime($event['start']));
                            $newTime = date("h:i A", strtotime($event['start']));
                            $eventUrl = 'club/'.$event['postId']. '?member-event=1';

                            // This is not a premium member event
                            if ($event['getId'] == '') {
                            $eventUrl = 'club/'.$event['postId']. '?member-event=1';
                            } else {
                            $eventUrl = 'club/'.$event['getId'];
                            }

                            @endphp
                            <div class="swiper-slide">
                                <div class="col-lg-12 col-sm-6">
                                    <div class="grid-widget" style="height: 335px;">
                                        <div class="widget-header">
                                            <a href="{{$eventUrl}}" target="_blank" class="opacity">
                                                <img width="370" height="150" src="{{ $event['postImage'] }}" alt="">
                                            </a>
                                        </div>
                                        <div class="widget-body">
                                            <h3>{{$event['postTitle']}}</h3>
                                            <p style="color:#818181;">{{$event['event_ttype']}}</p>
                                            <p class="date">
                                                <span class="float-left"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{$newDate}}</span>
                                                <span class="float-right"><i class="fa fa-clock" aria-hidden="true"></i> {{$newTime}}</span>
                                            </p>
                                            <a style="background-color: #dc273f;    padding: 8px 20px;    border-radius: 2px;    text-align: center;" href="{{$eventUrl}}" class="btn bg-red text-white">View Event</a>
                                            <div style="width:100%;display:inline-block;border-top: 1px solid #ddd;margin:16px 0 0;">
                                                <div class="float-right">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
                        <!-- Add Arrows -->
                        <div class="swiper-button-next">
                            <i class="fa fa-chevron-right"></i>
                        </div>
                        <div class="swiper-button-prev">
                            <i class="fa fa-chevron-left"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- <script src="https://static.elfsight.com/platform/platform.js" data-use-service-core defer></script>
    <div class="elfsight-app-a21a5165-a3bc-486e-bdbc-498216b35467" data-elfsight-app-lazy></div> --}}

    <!-- testimonials -->
    <section id="testimonials">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center">
                        <h2><span>Testimonials</h2>
                        <p>What Our Clients Say</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="swiper-container testimonials-container">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“The GLOBAL Woman Platform Networking has been a wonderful network both
                                        personally and professionally. It’s been a place to meet like-minded women and also
                                        find new clients.  For an entrepreneurial venture to succeed you need to be a part of a
                                        community, have a platform, and support to lean on to as you grow your business. 
                                        It is such a joy to know that I can always find like-minded women who are passionate
                                        about their businesses and at the same time give back to the business community
                                        through shared experiences and expertise. 
                                        The monthly network meetings are a “must” on my calendar and I always come away
                                        with new ideas and inspiration. The speakers are always knowledgeable and cover a
                                        broad range of relevant topics. I am grateful that Mirela had the big vision to connect
                                        women globally through the global woman platform. Grateful for that!”</p>
                                    <img alt="" src="https://davxegdvkjbks.cloudfront.net/club-images/90x90/1.jpg">
                                    <h3 class="testimonial-author">Giovana Vega</h3>
                                    <p class="testimonial-meta">Director of Global Woman Club - Amsterdam</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“Joining the Global Woman Club has been the best decision I have made in 2021. It was exactly what I needed. Thanks to GW I gained so much confidence in public speaking.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/28/ddeb260ad44f94ae9a06371855dcee/Mimi-Bland.jpg">
                                    <h3 class="testimonial-author">Mimi Blanda</h3>
                                    <p class="testimonial-meta">Transformational Mindset Coach, London</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“I heard many people talking about the Global Woman Club and one day I decided to visit it. At the beginning I stayed quiet, but then I realized that in such a community.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/af/8a5e9ae27545eab3acc45654957ab8/Sonia-Summan.jpg">
                                    <h3 class="testimonial-author">Sonia Summan</h3>
                                    <p class="testimonial-meta">Fashion Expert, London</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“When I discovered the Global Woman Club for the first time It was a new world for me. I loved it immediately. I knew I needed it. It was exactly the platform that could help.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/eb/618a2f51884627a09cfab0d51b5ec9/Daria-Fidecka.jpg">
                                    <h3 class="testimonial-author">Daria Fidecka</h3>
                                    <p class="testimonial-meta">Beauty Expert - London</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“Global Woman Club was life changing for me. I clicked with the whole atmosphere and energy immediately. The values, the culture and the space you find there is unique.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/ae/4c52fd70fc4392aed10624f74ea9f2/Enas-Daeki.jpg">
                                    <h3 class="testimonial-author">Enas Daeki</h3>
                                    <p class="testimonial-meta">Founder of Arabela Agency</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“Global Woman Club has been a complete blessing for me. The global woman community has been so supportive to me and has helped me to find my voice and to create.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/2f/f0e5b509614186b9ca83fd865056e4/Maria-Mladenovska.jpg">
                                    <h3 class="testimonial-author">Maria Mladenovska</h3>
                                    <p class="testimonial-meta">Founder, The Voice Inside, Greece</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“I was so excited to learn that the Global Woman Club was going to be represented in Accra, Ghana. It can be very challenging.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/ab/a93245ac2c497fb8b377dca0547eab/Mawuko-Fumey.jpg">
                                    <h3 class="testimonial-author">Mawuko Fumey</h3>
                                    <p class="testimonial-meta">Founder, Natural Skincare Manufacturing Company, Accra</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“What can I say about the global woman club, is that it is truly one of the best networking opportunities out there. Our local LA Chapter run by the brilliant and talented.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/c9/16b3a3853b4c30a6278fde4c86aeaf/Lisa-Browne.jpg">
                                    <h3 class="testimonial-author">Lisa Browne</h3>
                                    <p class="testimonial-meta">Clinical Hypnotherapist - LA</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“What attracted me to join the GW Frankfurt club is genuine women's support. One of my core values is authenticity. The GW platform allows me to do precisely this.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/b1/cb24cb69a640d6a86e2a34391192de/Alexandra-Katzer.jpg">
                                    <h3 class="testimonial-author">Alexandra Katzer</h3>
                                    <p class="testimonial-meta">Nature Coach, Frankfurt</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“Global Woman is a warm community that provides you all the possibilities to go global. You get the opportunity to meet like-minded female entrepreneurs abroad.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/32/843cef32bc4c0685269e2ae7390548/Idelma-del-Mar-Rust.jpg">
                                    <h3 class="testimonial-author">Idelma del Mar Rust</h3>
                                    <p class="testimonial-meta">Video pitch Expert - Amsterdam</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“Global Woman Amsterdam has given me a great international network. I have learned so much and about everything I need to grow my business. In fact I learned how.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/d5/981de2ddac4dcc909625046a77ec5b/Louky-de-Lange.jpg">
                                    <h3 class="testimonial-author">Louky de Lange</h3>
                                    <p class="testimonial-meta">Psychologist - Amsterdam</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“As entrepreneurs, we have to make many decisions. The most important decision is finding relationships and resources that give us the opportunity to be of service.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/ce/79d15c3d284d7ba7bf9e05eb361dee/Dr.-Janice-Hooker.jpg">
                                    <h3 class="testimonial-author">Dr. Janice Hooker Fortman</h3>
                                    <p class="testimonial-meta">Chicago</p>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="testimonial">
                                    <p class="testimonial-description">“Founder of Start in The Gut, GWC member in London Global Woman Club was the best discovery which came in the right time. I so much needed a community.”</p>
                                    <img alt="" src="https://club.globalwomanacademy.com/hosted/images/61/5f94f78b52453bbd3d7236828122dc/90e55bf6-1e87-4f01-b4e6-3c2840cfa30f.jpg">
                                    <h3 class="testimonial-author">Elma Reka</h3>
                                    <p class="testimonial-meta">London</p>
                                </div>
                            </div>
                        </div>
                        <!-- Add Arrows -->
                        <div class="swiper-button-next">
                            <i class="fa fa-chevron-right"></i>
                        </div>
                        <div class="swiper-button-prev">
                            <i class="fa fa-chevron-left"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('Public.Home.Partials.footer')
    <!-- jquery -->
    <script src="assets/javascript/jquery.min.js"></script>
    <!-- bootstrap -->
    <script src="assets/javascript/popper.min.js"></script>
    <script src="assets/javascript/bootstrap.min.js"></script>
    <!-- jquery easing -->
    <!-- <script src="assets/javascript/jquery.easing.min.js"></script> -->
    <!-- swiper -->
    <script src="assets/javascript/swiper/swiper-bundle.min.js"></script>
    <!-- custom -->
    <script src="assets/javascript/event.js"></script>
    <script src="https://globalwomanlounge.com/saas/js/plugins/jquery.countdown/jquery.countdown.min.js"></script>
    <script>
        $('[data-countdown]').each(function() {
            var $this = $(this),
                finalDate = $(this).data('countdown');
            $this.countdown(finalDate, function(event) {
                $this.html(event.strftime('%DD %-HH %MM %SS'));
            });
        });
        $('[data-countdown]').each(function() {
            var $this = $(this),
                finalDate = $(this).data('countdownwebinar');
            $this.countdownwebinar(finalDate, function(event) {
                $this.html(event.strftime('%DD %-HH %MM %SS'));
            });
        });
        $('[data-countdown]').each(function() {
            var $this = $(this),
                finalDate = $(this).data('countdownwebinarall');
            $this.countdownwebinarall(finalDate, function(event) {
                $this.html(event.strftime('%DD %-HH %MM %SS'));
            });
        });
        $('[data-countdown]').each(function() {
            var $this = $(this),
                finalDate = $(this).data('countdownbig1');
            $this.countdownbig1(finalDate, function(event) {
                $this.html(event.strftime('%DD %-HH %MM %SS'));
            });
        });
        $('[data-countdown]').each(function() {
            var $this = $(this),
                finalDate = $(this).data('countdownbig2');
            $this.countdownbig2(finalDate, function(event) {
                $this.html(event.strftime('%DD %-HH %MM %SS'));
            });
        });
    </script>

    <script src="https://globalwomanlounge.com/ads/embed.js?placement=global-woman-events-home" async></script>

</body>

</html>
