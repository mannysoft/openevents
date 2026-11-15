<div>
    {{-- Saved card --}}
    @if($saved && !$editing)
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong>Friend Details</strong>
            <span class="pull-right">
                <button type="button" wire:click="edit" class="btn btn-xs btn-default">Edit</button>
                <button type="button" wire:click="remove"
                        wire:confirm="Remove your friend's details?"
                        class="btn btn-xs btn-danger">Remove</button>
            </span>
        </div>
        <div class="panel-body">
            <p><strong>Name:</strong> {{ $firstName }} {{ $lastName }}</p>
            <p><strong>Email:</strong> {{ $email }}</p>
            @if($phone)
            <p><strong>Phone:</strong> {{ $phone }}</p>
            @endif
        </div>
    </div>
    @else
    {{-- Form --}}
    <form wire:submit="save">
        <div class="form-group">
            <label>First Name <span class="text-danger">*</span></label>
            <input type="text" wire:model="firstName" class="form-control" placeholder="Friend's first name">
            @error('firstName') <span class="help-block text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label>Last Name <span class="text-danger">*</span></label>
            <input type="text" wire:model="lastName" class="form-control" placeholder="Friend's last name">
            @error('lastName') <span class="help-block text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label>Email Address <span class="text-danger">*</span></label>
            <input type="email" wire:model="email" class="form-control" placeholder="Friend's email address">
            @error('email') <span class="help-block text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label>Phone Number <em class="text-muted">(optional)</em></label>
            <input type="tel" wire:model="phone" class="form-control" placeholder="Friend's phone number">
            @error('phone') <span class="help-block text-danger">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove>Save Friend Details</span>
            <span wire:loading>Saving…</span>
        </button>

        @if($editing)
        <button type="button" wire:click="$set('editing', false); $set('saved', true)" class="btn btn-default" style="margin-left:5px;">
            Cancel
        </button>
        @endif
    </form>
    @endif
</div>
