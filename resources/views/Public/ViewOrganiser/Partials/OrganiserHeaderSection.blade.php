<section id="intro" class="container">
  <div class="organiser_view">
    <div class="organiser_logo">
        <img src="{{URL::to($organiser->full_logo_path)}}" />
    </div>
    <div class="organiser_info">
      <h1>{{$organiser->name}}</h1>
      @if($organiser->about)
      <div class="description">
          {!! $organiser->about !!}
      </div>
      @endif
    </div>
  </div>
</section>
