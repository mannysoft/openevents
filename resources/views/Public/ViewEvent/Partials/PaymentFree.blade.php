<form class="online_payment ajax" action="<?php echo route('postCreateOrder', ['event_id' => $event->id]); ?>"
      method="POST" id="free-payment-form">

    <p>
        {!! @trans('Public_ViewEvent.no_payment_required') !!}
    </p>

    <div>
            <label>First Name</label>
            <input class="form-control" name="firstname" value="{{ session('firstname') }}" placeholder="Enter First Name" required>
        </div>

        <div>
            <label>Last Name</label>
            <input class="form-control" name="lastname" value="{{ session('lastname') }}" placeholder="Enter Last Name" required>
        </div>

        <div>
            <label>Email</label>
            <input type="email" class="form-control" name="email" value="{{ session('email') }}" placeholder="Enter Email" required>
        </div>

        <!-- Show only if the user dont have phone number in database -->
        <div style="margin-bottom: 15px">
            <label>Phone Number</label>
            <input class="form-control" name="phone" value="{{ session('phone') }}" required>
        </div>

    <div id="form-errors"></div>

    @csrf

    <input class="btn btn-lg btn-success card-submit" style="width:100%;" type="submit"
           value="{!! @trans('Public_ViewEvent.continue') !!}">

</form>
