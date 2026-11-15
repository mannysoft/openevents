<div>
    @if (! (bool)$this->purchased)
        @if ($addedToCart == false)
            <div class="grid-box">
            <br>
            <h3>Available Speaking Slots</h3>
            @foreach ($speakingSlots as $slot)
            <br>
            @if ($slot->sold < $slot->max_slot)
            {{-- <div class="bottom --flex-row">
                <div class="barcode"></div>
                
                <strong>{{ $slot->name }}</strong>
                
                <div>
                    <strong>{{ $slot->currency }} {{ $slot->price }}</strong>
                    <button style="background-color: #f86c6b;color:white" type="button" wire:click="addCart({{ $slot->id }})">Add</button>
                </div>
                
            </div> --}}

            <div class="card" style="width: 100%">
                {{-- <img src="..." class="card-img-top" alt="..."> --}}
                <div class="card-body">
                    <h5 class="card-title">{{ $slot->name }}</h5>
                    <p class="card-text">{{ $slot->currency }} {{ $slot->price }}</p>
                    <a wire:click.prevent="addCart({{ $slot->id }})" class="btn btn-sm btn-danger">Add</a>
                </div>
            </div>
            @endif
                
            @endforeach
        </div>
        @else
            <div class="grid-box">
                <br>
                <h3>Available Speaking Slots</h3>
                @foreach ($speakingSlots as $slot)
                <br>
                    @if (session('speaking_slot') == $slot->id)
                        
                        {{-- <div class="bottom --flex-row">
                            <div class="divider"></div>
                            
                            <strong>{{ $slot->name }}</strong>
                            
                            <div>
                                <strong>{{ $slot->currency }} {{ $slot->price }}</strong>
                                <button style="background-color: #f86c6b;color:white" type="button" wire:click="removeCart({{ $slot->id }})">Remove</button>
                            </div>
                            
                        </div> --}}
                        <div class="card" style="width: 100%">
                            <div class="card-body">
                                <h5 class="card-title">{{ $slot->name }}</h5>
                                <p class="card-text">{{ $slot->currency }} {{ $slot->price }}</p>
                                <a wire:click.prevent="removeCart({{ $slot->id }})" class="btn btn-xm btn-danger">Remove</a>
                            </div>
                        </div>
                    @endif
            
                @endforeach
            </div>
        @endif
    @endif
</div>
