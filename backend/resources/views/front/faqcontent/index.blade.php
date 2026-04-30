@extends('front.layout.main')
@section('content')
<div class="shop-center-tophead">
    <img src="{{ asset('front/img/service-inner-bg.webp') }}" class="img-fluid" alt="">
    <div class="shop-center-text">
        <h1 class="text-white">{{ strtoupper($site_title) }}</h1>
        <ul class="shop-center-breadcum">
            <li><a href="{{url('/')}}">Home</a></li>
            <li><i class="fa-solid fa-angles-right"></i></li>
            <li>{{ $site_title }}</li>
        </ul>
    </div>
</div>

<div class="faqcontent-section-main">
    <div class="container">
        <div class="row justify-content-center">
            <div class=" col-lg-10">
                <div id="accordion" class="accordion">
                    @if($faqcontents->count())
                        @foreach($faqcontents as $key => $faqcontent)
                            <div class="accordion-box faqcontent-text-content">
                                <a href="#" class="accordion-header @if($key == 0) {{'active-accordion'}} @endif" data-target="acrd_1">{{ $faqcontent->name }}</a>
                                <div class="accordion-content" id="acrd_{{$key+1}}" style="@if($key == 0) {{'display:block'}} @endif">
                                    <p class="accordion-text-content">
                                        {!! $faqcontent->description !!}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<!-- faqcontent page end -->
@endsection
@section('javascript')
<script>
    $(function() {
	    $(".accordion-header").click(function(event) {
		    event.preventDefault();
		    var dis = $(this);
		    var acr_box = dis.closest(".accordion");
            if(!dis.hasClass("active-accordion")){
                acr_box.find(".accordion-header").removeClass("active-accordion");
                dis.addClass("active-accordion");
                acr_box.find(".accordion-content").slideUp();
                dis.next().stop(true,true).slideToggle();
            }
	    });
    });    
</script>
@endsection