<section id="section-galleryCard" class="section-galleryCard" style="background-color: @if(isset($globalmanclub) && $globalmanclub == true) #fff @endif;">
    {{ $displayTitle }}
    <div class="container">
        <div class="row">
            <div class="col-lg-12">

                @foreach($items as $key => $item)
                    <div class="card">
                        <div class="row no-gutters">
                            <div class="col-md-6 align-self-center">
                                @if (isset($can_edit) && $can_edit == true && isset($clubData) && $clubData->id != null && request('edit_event') == 1 && (isset($is_admin) && $is_admin == true))
                                <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="message_mirela" data-club-id="{{ $clubData->id }}" data-content="{{ $clubData->message_mirela }}" data-has-file-upload="true" data-label="Message fron Mirela section">
                                    Edit
                                </a>
                                @endif

                                @if(is_admin() && $isEdit)
                                 <a class="btn btn-light" data-toggle="modal" data-target="#editContentModal" data-content-field="magazine_section_{{ $item->id }}" data-club-id="" data-content="{{ $item->section_content }}" data-has-file-upload="true" data-label="Magazine section">
                                    Edit
                                </a>
                                @endif
                                
                                <img src="{{ $item->section_image }}" class="card-img" alt="">
                            </div>
                            <div class="col-md-6 align-self-center">
                                <div class="card-body">
                                    {!! $item->section_content !!}
                                </div>
                            </div>
                        </div>
                        @if(is_admin() && $isEdit && $item->is_placeholder == false)
                            <button wire:click="removeSection({{ $item->id }})" type="button" class="book" value="Submit">Remove Section</button>
                        @endif
                    </div>
                @endforeach

                @if(is_admin() && $isEdit)
                   <button wire:click="addSection" type="button" class="book" value="Submit">Add Section</button>
                @endif
                 
            </div>
        </div>
    </div>
</section>