<!-- Public.ClubEvents.Partials.SectionOne -->
<section id="section-one" class="section-one" style="background-color:rgba(16, 16, 16, 0.83)">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-sm-12 @if(isset($textColorClass)) {{ $textColorClass }} @endif">
                <h1>{{$event->title}} <br />{{date('l jS \ F',strtotime($event->start_date))}}</h1>

                <p class="m-0">Date: {{date('l jS \ F Y',strtotime($event->start_date))}}</p>
                <p class="m-0">Time: {{date('h:i A',strtotime($event->start_date))}} - {{date("h:i A", strtotime($event->end_date))}} ({{ $event->event_timezone_format ?? '' }} Time)</p>
                <p class="m-0">{{ $event->location }}</p>

            </div>
            <div class="col-lg-6 col-sm-12 text-center align-self-center">
                <a id="book-ticket-here" style="margin-top: 10px;" href="#section-ticket-book" class="btn @if(isset($bgColorNeonClass)) {{ $bgColorNeonClass}} @else bg-red @endif text-white">Book Your Ticket Here</a>
                <!-- Book -->
                @if (session('member') && request('ref') == null && env('REFERRAL_ENABLED', false))
                {{-- Show only when logged in and user is a member --}}
                <a style="margin-top: 10px;" href="javascript:void(0);" data-toggle="modal" data-target="#inviteNonMembersModal" class="btn bg-red text-white">Invite Non-Members</a>
                @endif
            </div>
        </div>
    </div>
</section>

@if (session('member'))
<!-- Invite Non-Members Modal -->
<div class="modal fade" id="inviteNonMembersModal" tabindex="-1" aria-labelledby="inviteNonMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inviteNonMembersModalLabel">Invite Non-Members</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Share this unique link with non-members to invite them to this event:</p>
                <div class="input-group">
                    @php
                        $inviteUrl = generate_referal_code();
                    @endphp
                    <input type="text" id="inviteLink" class="form-control form-control-lg" value="{{ $inviteUrl }}" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-primary btn-sm" type="button" id="copyInviteLink" onclick="copyInviteLink()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <small id="copySuccess" class="text-success mt-2" style="display:none;">Link copied to clipboard!</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    function copyInviteLink() {
        var linkInput = document.getElementById('inviteLink');
        linkInput.select();
        linkInput.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(linkInput.value).then(function() {
            var successMsg = document.getElementById('copySuccess');
            successMsg.style.display = 'block';
            setTimeout(function() {
                successMsg.style.display = 'none';
            }, 3000);
        });
    }
</script>
@endif