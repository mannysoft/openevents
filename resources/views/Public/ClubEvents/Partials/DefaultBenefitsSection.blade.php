<!-- Public.ClubEvents.Partials.BenefitsSection -->
<section id="section-quote" class="section-quote" style="background-color:rgba(136, 136, 136, 0.33)">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <h4>Discover benefits of being a member of Global Woman Club</h4>

                </div>
            </div>
        </div>
    </div>
</section>

<section id="section-benefits" class="section-benefits">
    <div class="container">


        <div class="row">
            <div class="col-lg-12">
                <div>
                    @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                    <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="benefits" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->benefits }}" data-label="Benefits">
                        Edit
                    </a>
                    @endif
                    @if (isset($clubData) && $clubData->benefits != '' && $clubData->benefits != null )
                    {!! $clubData->benefits !!}
                    @else
                    <ol>
                        <li style="font-family:'loto';font-size:20px">As a member, you can attend any Global Woman Club meeting organised around the world, and speak in front of an international audience. You can network, create meaningful connections, share your story, seek help, offer your help and promote your projects</li></br>
                        <li style="font-family:'loto';font-size:20px">You can promote and sell your business services and keep 100% of revenue</li></br />
                        <li style="font-family:'loto';font-size:20px">Our members have an exclusive opportunity to join our in-person and online meetings to promote their projects, and we broadcast their message on all our platforms including the Global Woman Lobby</li></br>
                        <li style="font-family:'loto';font-size:20px">We run numerous online meetings each month</li></br>
                        <li style="font-family:'loto';font-size:20px">All Global Woman members have complimentary access to Global Woman Online Academy</li></br>
                        <li style="font-family:'loto';font-size:20px">As a Global Woman Club member, you will have access to the Member's Lounge - a powerful platform created for private communication between members of Global Woman Club all over the world</li></br>
                        <li style="font-family:'loto';font-size:20px">You can join weekly online Mastermind sessions with world class speakers, trainers and experts, and take part in the Q&A </li></br>
                        <li style="font-family:'loto';font-size:20px">We record all meetings and Masterminds, and our members can access the recordings at any time from the online library content in Global Woman Academy </li></br>
                    </ol>
                    @endif
                    <div class="row">
                        <div class="col-lg-4 col-sm-12">

                            </br>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 col-sm-12">

                        </div>
                        <div class="col-lg-4 col-sm-12">
                            <a href="https://subscription.globalwomanclub.com/index" target="_blank" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white" style="font-family:'loto';font-size:20px"> Join Global Woman Today</a>
                        </div>
                        <div class="col-lg-4 col-sm-12">

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>