<footer>

<div class="footer-soical-icon">
    <a href="https://www.facebook.com/ACRautocarrepair" target="blank"><i class="fa-brands fa-facebook-f"></i></a>
    <a href="https://www.instagram.com/autocarrepair_/" target="blank"><i class="fa-brands fa-instagram"></i></a>
    <a href="https://twitter.com/Auto_carrepair" target="blank"><img src="{{ asset('front/img/twitter.png') }}"  alt=""></a>
    <a href="https://www.linkedin.com/company/autocarrepairacr/" target="blank"><i class="fa-brands fa-linkedin"></i></a>
    <a href="https://www.youtube.com/@Auto_carrepair/featured" target="blank"><i class="fa-brands fa-youtube"></i></a>
    <a href="https://wa.me/9810446692" target="blank"><i class="fa-brands fa-whatsapp"></i></a>
</div>

<div class="newfooter-bg-main">
    <div class="row m-0">
        <div class="col-12 col-sm-6 col-md-3">
            <ul class="our-service-main">
                <li class="our-section-head">OUR SERVICE</li>
                @php($services = getServiceCategory())
                @if($services->count())
                    @foreach($services as $service)
                        @php($sslug = getDefualtServiceSlug())
                        <li><a href="{{url($service->slug.'/'.$sslug)}}">{{$service->title}}</a></li>
                    @endforeach
                @endif
            </ul>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <ul class="our-service-main">
                <li class="our-section-head">LUXURY BRANDS</li>
                @php($compnyInfo = getCompnyCmsPages())
                @if(isset($compnyInfo['second_section']))
                    @foreach($compnyInfo['second_section'] as $info)
                        @if(isset($info->slug) && $info->slug)
                            <li><a href="{{url($info->slug)}}">{{isset($info->name) ? ucwords($info->name) : ''}}</a></li>                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <ul class="our-service-main">
                <li class="our-section-head">POPULAR BRANDS</li>
                @if(isset($compnyInfo['third_section']))
                    @foreach($compnyInfo['third_section'] as $info)
                        @if(isset($info->slug) && $info->slug)
                            <li><a href="{{url($info->slug)}}">{{isset($info->name) ? ucwords($info->name) : ''}}</a></li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <ul class="our-service-main">
                <li class="our-section-head">POPULAR AREAS NEAR YOU</li>
                @if(isset($compnyInfo['forth_section']))
                    @foreach($compnyInfo['forth_section'] as $info)
                        @if(isset($info->slug) && $info->slug)
                            <li><a href="{{url($info->slug)}}">{{isset($info->name) ? ucwords($info->name) : ''}}</a></li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
    </div>
</div>
<div class="footer-address">
    <div class="row m-0 align-items-center">
        <div class="col-12 col-sm-2">
            <div>
                <img src="{{ asset('front/img/MY TVS logo.png') }}" class="acr-my-tvsimage"  alt="">
            </div>
        </div>
        <div class="col-12 col-sm-4">

                <div class="phn">

                    <img src="{{ asset('front/img/call-image.webp') }}" class="call-image-main" alt="">

                   <a href="tel:{{$phone}}" class="footer-number-main">{{$phone}}</a>

                </div>
        </div>
        <div class="col-12 col-sm-6">
  <div class="slideshow-container">
    <div class="mySlides">

      <a href="https://maps.app.goo.gl/2JxotW8WRLkhBs5bA"><p class="author">Auto Car Repair(ACR) Gurugram </p></a>

      <a href="https://maps.app.goo.gl/2JxotW8WRLkhBs5bA"><q class="authorp">Unit-1 Plot, 29 & 30, near Kargil Shaheed Sukhbir Singh Yadav Marg, Info Technology Park, Sector 34,

        Gurugram, Haryana 122001</q></a>

    </div>
    <div class="mySlides">

      <a href="https://maps.app.goo.gl/LzE6Y3denNPcWerY7"><p class="author" style="margin-bottom:20px;">Auto Car Repair(ACR) MotiNagar</p></a>

      <a href="https://maps.app.goo.gl/LzE6Y3denNPcWerY7"><q class="authorp">B-60 Block B Rama Road, Moti Nagar, Delhi, 110015</q></a>

    </div>
    <div class="mySlides">

      <a href="https://maps.app.goo.gl/2JxotW8WRLkhBs5bA"><p class="author">Auto Car Repair(ACR) Gurugram </p></a>

      <a href="https://maps.app.goo.gl/2JxotW8WRLkhBs5bA"><q class="authorp">Unit-1 Plot, 29 & 30, near Kargil

        Shaheed Sukhbir Singh Yadav Marg,

        Info Technology Park, Sector 34,

        Gurugram, Haryana 122001</q></a>

    </div>
    <div class="mySlides">

      <a href="https://maps.app.goo.gl/LzE6Y3denNPcWerY7"><p class="author" style="margin-bottom:20px;">Auto Car Repair(ACR) MotiNagar</p></a>

      <a href="https://maps.app.goo.gl/LzE6Y3denNPcWerY7"><q class="authorp">B-60 Block B Rama Road, Moti Nagar, Delhi, 110015</q></a>

    </div>
    </div>
    <div class="dot-container">

      <span class="dot" onclick="currentSlide(1)"></span> 

      <span class="dot" onclick="currentSlide(2)"></span> 

      <span class="dot" onclick="currentSlide(3)"></span> 

    </div>
        </div>
    </div>
</div>
<div class="footer-menu-main">
    <div>
        <ul class="footer-menu-item">
            <li><a href="{{url('about-us')}}">About Us</a></li>
            <li><a href="{{url('faqs')}}">FAQs</a></li>
            <li><a href="{{url('contact-us')}}">Contact Us</a></li>
            <li><a href="https://acr-mechanic.com/blog/" target="_blank">Blog</a></li>
            @php($cmsInfo = getCmsPageName('10'))
                @if(isset($cmsInfo->slug) && $cmsInfo->slug)
                    <li><a href="{{url($cmsInfo->slug)}}">{{isset($cmsInfo->name) ? ucwords($cmsInfo->name) : ''}}</a></li>
                @endif
            @php($cmsInfo = getCmsPageName('12'))
            @if(isset($cmsInfo->slug) && $cmsInfo->slug)
                <li><a href="{{url($cmsInfo->slug)}}">{{isset($cmsInfo->name) ? ucwords($cmsInfo->name) : ''}}</a></li>
            @endif
        </ul>
    </div>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}" />

</footer>

<!-- footer down end -->


<script>

            var slideIndex = 1;

        showSlides(slideIndex);
        function plusSlides(n) {

        showSlides(slideIndex += n);

        }
        function currentSlide(n) {

        showSlides(slideIndex = n);

        }
        function showSlides(n) {

        var i;

        var slides = document.getElementsByClassName("mySlides");

        var dots = document.getElementsByClassName("dot");

        if (n > slides.length) {slideIndex = 1}    

        if (n < 1) {slideIndex = slides.length}

        for (i = 0; i < slides.length; i++) {

            slides[i].style.display = "none";  

        }

        for (i = 0; i < dots.length; i++) {

            dots[i].className = dots[i].className.replace(" active", "");

        }

        slides[slideIndex-1].style.display = "block";  

        dots[slideIndex-1].className += " active";

        }

        window.onload= function () {

        setInterval(function(){ 

        plusSlides(1);

        }, 3000);

        }
</script>
<script src="{{ asset('front/js/jquery.min.js') }}"></script>
<script src="{{ asset('front/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('front/js/all.min.js') }}"></script>
<script src="{{ asset('plugins/notification/toastr.min.js') }}"></script>
<script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>

<script src="{{ asset('front/js/owl.carousel.min.js') }}"></script>
<script>
    $(document).ready(function() {
        basic();
        // notification //
        <?php if (Session::get('error')) : ?>
            toastr.error('<?php echo Session::get('error') ?>');
        <?php endif; ?>
        <?php if (Session::get('errors')) : ?>
            toastr.error('<?php echo Session::get('errors')->first() ?>');
        <?php endif; ?>
        <?php if (Session::get('success')) : ?>
            toastr.success('<?php echo Session::get('success') ?>');
        <?php endif; ?>
        <?php if (Session::get('warning')) : ?>
            toastr.warning('<?php echo Session::get('warning') ?>');
        <?php endif; ?>

        $('.btn-toggle-item').click(function(){
            $('.mobile-toggle-data').toggle();
        });

        $(window).scroll(function () { 
            if ($(window).scrollTop() > 50) {
                $('#header-sticky').addClass('sticky');
                $('#up-button-main').addClass('up-btn-sticky');
                $('#up-button-main').css('display','block');

                $('.mobile-menu-main').addClass('sticky');
            }
            if ($(window).scrollTop() < 51) {
                $('#header-sticky').removeClass('sticky');
                $('#up-button-main').removeClass('up-btn-sticky');
                $('#up-button-main').css('display','none');

                $('.mobile-menu-main').removeClass('sticky');

            }
        });

        $(document).on('keyup', '#search_brand', function(){
            var search_brand = $(this).val();
            searchBrand(search_brand);
        });

        $(document).on('click', '.amodal-brand', function(){
            var brand_id = $(this).data('id');
            modelFromBrandSearch(brand_id, '');
        });

        $(document).on('keyup', '#search_model', function(){
            var search_model = $(this).val();
            var brand_id = "{{session()->get('brand_id')}}";
            modelFromBrandSearch(brand_id, search_model);
        });

        $(document).on('click', '.amodal-model', function(){
            var model_id = $(this).data('id');
            fuelFromModelSearch(model_id, '');
        });

        $(document).on('keyup', '#search_fuel', function(){
            var search_fuel = $(this).val();
            fuelFromModelSearch('', search_fuel);
        });

        $(document).on('click', '.amodal-fuel', function(){
            var fuel_id = $(this).data('id');
            appointmentnumberModal(fuel_id);
        });

        $(document).on('click', '.apt-btn', function(){
            appointmentnumberModal(fuel_id = '');
        });
        $('#appointmentselectModal').on('hidden.bs.modal', function() {
            $('#search_brand').val('');
        });
        $('#appointmentsearchModal').on('hidden.bs.modal', function() {
            $('#search_model').val('');
        });
        $('#appointmentfuelModal').on('hidden.bs.modal', function() {
            $('#search_fuel').val('');
        });
        $('#back-from-fuel-popup').click(function() {
            var brand_id = $(this).attr('data-brand_id');
            $('#appointmentfuelModal').hide();
            modelFromBrandSearch(brand_id);
        });
        $('#back-from-number-popup').click(function() {
            var model_id = $(this).attr('data-model_id');
            $('#appointmentnumberModal').hide();
            fuelFromModelSearch(model_id);
        });        $(document).on('click', '#check_price', function(){
            //location.href = "{{url('our-services')}}";
            var is_service_page = $('#is_service_page').val();
            if(is_service_page == 1){
                var category_slug = $('#current_service_slug').val();
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                   url: "{{ route('front_get-current-model') }}", // Change to your route name
                   type: "POST",
                   data: {category_slug: category_slug, _token: csrfToken},
                   success: function(data) {
                        var result = $.parseJSON(data);
                        var model_slug = result.slug;
                        var href = "{{url('/')}}"+"/"+category_slug+"/"+model_slug;
                        location.href = href;
                   },
                   error: function(jqXHR, textStatus, errorThrown) {
                       console.error('AJAX Error: ' + textStatus, errorThrown);
                   }
               });
            } else {
                window.location.reload();
            }
        });
       
        $('#appointmentresend_otp').hide();
        $('.aptotp-section').hide();
        $(document).on('click', '#appointmentsend_otp', function(){
            var validateMobNum= /[1-9]{1}[0-9]{9}/;
            var mobile = $('#appointmentmobile').val();
            if (validateMobNum.test(mobile) && mobile.length == 10) {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url : '{{ route('front_send-otp') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, mobile:mobile},
                    success : function(result){
                        var result = $.parseJSON(result);
                        if(result.result == 'success'){
                            $("#appointmentmobile").attr("readonly", "readonly");
                            $('.aptotp-section').show();
                            $('#appointmentsend_otp').hide();
                            apttimer(30);
                        } else {
                            toastr.error('Something went wrong. Please try again later!');
                        }
                    }
                });
            }
            else {
                toastr.error('Please Enter Valid Mobile No.');
            }
        });

        $(document).on('keyup', '#appointmentotp', function(){
            var mobile = $('#appointmentmobile').val();
            var otp = $('#appointmentotp').val();
            var olength = otp.toString().length;
            if(parseInt(olength) > 3){
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url : '{{ route('front_verify-otp') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, mobile:mobile, otp:otp},
                    success : function(result){
                        var result = $.parseJSON(result);
                        if(result.result == 'success'){
                            localStorage.setItem("phone", mobile);
                            $('#appointmentresend_text').hide();
                            $('#appointmentis_otp_verify').val('1');
                            $('#check_price').show();
                            $("#appointmentmobile").attr("readonly", "readonly"); 
                            $('#appointmentotp').hide();
                        } else {
                            toastr.error('Please Enter Valid OTP.');
                        }
                    }
                });
            }
        });

        $(document).on('click', '#appointmentresend_otp', function(){
            var validateMobNum= /[1-9]{1}[0-9]{9}/;
            var mobile = $('#appointmentmobile').val();
            if (validateMobNum.test(mobile) && mobile.length == 10) {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url : '{{ route('front_resend-otp') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, mobile:mobile},
                    success : function(result){
                        var result = $.parseJSON(result);
                        if(result.result == 'success'){
                            $('.aptotp-section').show();
                            $('#appointmentresend_text').show();
                            $('#appointmentotp').val('');
                            $('#appointmentotp').show();
                            $("#appointmentmobile").attr("readonly", "readonly");
                            $('#appointmentresend_otp').hide();
                            apttimer(30);
                        } else {
                            toastr.error('Something went wrong. Please try again later!');
                        }
                    }
                });
            }
            else {
                toastr.error('Please Enter Valid Mobile No.');
            }
        });

        $(document).on('keypress', '.search_text',function(e) {
            var $this = $(this);
            if (e.keyCode === 13) {
                var search = $this.val();
                if(search){
                    var href = "{{route('front_search')}}"+'?search='+search;
                    window.location.href = href;
                }
            }
        });
    //
    });

    function basic(){
        $("input").attr("autocomplete", "off");
        $("textarea").attr("autocomplete", "off");
        $("input[type=password]").attr("autocomplete", "new-password");
        $(".numeric").bind("keypress", function (e) {
            var keyCode = e.which ? e.which : e.keyCode;
            if (!((keyCode >= 48 && keyCode <= 57) || keyCode == 46)) {
                return false;
            }
        });
        $(".num_only").bind("keypress", function (e) {
            var keyCode = e.which ? e.which : e.keyCode;
            if (!((keyCode >= 48 && keyCode <= 57))) {
                return false;
            }
        });
        $(document).on('keypress', '.alphabetic', function (event) {
            var regex = new RegExp("^[a-zA-Z ]+$");
            var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
            if (!regex.test(key)) {
                event.preventDefault();
                return false;
            }
        });
        $(document).on('keypress', '.alphanumeric', function (event) {
            var regex = new RegExp("^[a-zA-Z0-9 ]+$");
            var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
            if (!regex.test(key)) {
                event.preventDefault();
                return false;
            }
        });
        setCartItemCount();
    }
    function PreviewImage(no) 
    {
        var oFReader = new FileReader();
        oFReader.readAsDataURL(document.getElementById("uploadImage"+no).files[0]);
        oFReader.onload = function (oFREvent) 
        {
            document.getElementById("uploadPreview"+no).src = oFREvent.target.result;
            $('#uploadPreview'+no).removeClass('npPreviewImage');
            $('#uploadPreview'+no).addClass('previewImage');
            $('#uploadPreview'+no).css('width', '250px');
        };
    }

    function setCartItemCount(){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('front_cart-item-count') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN},
            success : function(result){
                var result = $.parseJSON(result);
                if(result.total){
                    $('.cart_header_total_item').html('('+result.total+')');
                }
            }
        });
    }

    function searchBrand(search_brand = ''){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('front_search-brand') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, brand: search_brand},
            success : function(result){
                var result = $.parseJSON(result);
                $('#amodal_brands').html(result.html);
            }
        });
    }

    function modelFromBrandSearch(brand_id = '', search_model = ''){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('front_model-from-brand-modal') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, brand_id: brand_id, model:search_model},
            success : function(result){
                var result = $.parseJSON(result);
                $('#amodal_models').html(result.html);
                $('#appointmentsearchModal').modal('show');
                $('#appointmentselectModal').modal('hide');
            }
        });
    }

    function fuelFromModelSearch(model_id = '', search_fuel = ''){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('front_search-fuel-from-model') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, model_id: model_id, fuel: search_fuel},
            success : function(result){
                var result = $.parseJSON(result);
                $('#amodal_fuels').html(result.html);
                $('#appointmentfuelModal').modal('show');
                $('#appointmentsearchModal').modal('hide');
                $('#back-from-fuel-popup').attr('data-brand_id', result.brand_id);
            }
        });
    }

    function appointmentnumberModal(fuel_id){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('front_appoitment-number-modal') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, fuel_id: fuel_id},
            success : function(result){
                var result = $.parseJSON(result);
                $('#appointmentfuelModal').modal('hide');
                if(result.result == 'success' && result.type == 'number'){
                    $('#search_info').html(result.html);
                    $('#appointmentnumberModal').modal('show');
                    $('#back-from-number-popup').attr('data-model_id', result.model_id);
                } else if(result.result == 'success' && result.type == 'fuel'){
                    fuelFromModelSearch();
                } else if(result.result == 'success' && result.type == 'model'){
                    modelFromBrandSearch();
                } else {
                    $('#appointmentselectModal').modal('show');
                }
                var localstorage_phone = localStorage.getItem("phone");
                $("#appointmentmobile").val(localstorage_phone);
                $('#appointmentresend_text').hide();
                $('#appointmentis_otp_verify').val('1');
                $('#check_price').show();
                $('#appointmentotp').hide();
                $('#appointmentsend_otp').hide();
            }
        });

        $('#aboutus-brand-carousel').owlCarousel({
            loop: true,
            margin: 30,
            dots: false,
            nav: false,
            items: 4,
            autoplay:true,
            autoplayTimeout:2000,
            autoplayHoverPause:true,
            responsiveClass: true,
            responsive: {
                0: {
                items: 1
                },
                450:{
                items: 2
                },
                600: {
                items: 3
                },
                1024: {
                items: 4
                }
            }
        });

    }
    let timerStart = true;
    function apttimer(remaining) {
        var m = Math.floor(remaining / 60);
        var s = remaining % 60;
        m = m < 10 ? '0' + m : m;
        s = s < 10 ? '0' + s : s;
        document.getElementById('apttimer').innerHTML = m + ':' + s;
        remaining -= 1;
        if(remaining >= 0 && timerStart) {
        setTimeout(function() {
            apttimer(remaining);
        }, 1000);
        return;
        }

        if(!timerStart) {
        // Do validate stuff here
        return;
        }
        // Do timeout stuff here
        var is_otp_verify = $('#appointmentis_otp_verify').val();
        if(is_otp_verify == '0'){
            $('#appointmentresend_otp').show();
            $("#appointmentmobile").removeAttr("readonly"); 
            $('#appointmentresend_text').hide();
            $('#appointmentotp').hide();
        }
    }

</script>

@yield('javascript')

</body>
</html>