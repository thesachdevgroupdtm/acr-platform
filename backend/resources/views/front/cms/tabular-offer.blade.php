@extends('front.layout.main')

@section('content')

<!-- contact-us page start  -->

<?php ?>

<!-- contact-us page end -->

 <div class="popup-wrapper" id="popupWrapper">

        <div class="popup">

            <button class="close-btn" id="closeForm"></button>

          
        </div>

    </div>





<script>

    document.addEventListener("DOMContentLoaded", function () {

        let popup = document.getElementById("popupWrapper");

        let popupBtn = document.getElementById("openForm");

        let popupClose = document.getElementById("closeForm");



        <?php if(empty($contact_cookie_id)) : ?>

        showPopup();

        <?php endif; ?>



        popupBtn.addEventListener("click", function (e) {

            e.preventDefault();

            showPopup();

        });



        popupClose.addEventListener("click", function (e) {

            e.preventDefault();

            removePopup();

        });



        function showPopup() {

            popup.classList.add("active");

            bodyScroll();

        }



        function removePopup() {

            popup.classList.remove("active");

            bodyScroll();

        }



        function bodyScroll() {

            document.body.classList.toggle("no-scroll");

        }

    });

</script>









<div class="tabular-offer-cms-section-main">

    @if(isset($compnypageInfo->banner_image) && $compnypageInfo->banner_image)

        <img src="{{url('uploads/compnycms/'.$compnypageInfo->banner_image)}}" class="cms-image-main" alt="" title="{{isset($compnypageInfo->image_title) ? $compnypageInfo->image_title : ''}}">

    @endif

    <div class="cms-section-text">

        <h1>{{ isset($compnypageInfo->banner_text) ? $compnypageInfo->banner_text : '' }}</h1>

        <a class="Request-appointmentbtn" href="#" id="openForm">Book Car Service</a>





   

    </div>

</div>

<div class="container">

    <div class="cms-form-text-sec">

        <div class="row">

            <div class="col-12  col-md-6 col-lg-8">

                <p>{!! isset($compnypageInfo->description) ? $compnypageInfo->description : '' !!}</p>

                <!-- <p>It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here'

                It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here''</p>

                <p>It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here'</p> -->

                <!--<button class="book-service-cms apt-btn">Book A Service</button>-->

                

            </div>

            <div class="col-12 col-md-6 col-lg-4 p-0 ">

                <div class="cms-page-section">

                    <h3 class="request-heading-main">Request an Appointment</h3>

                 

                </div>

            </div>

        </div>

    </div>

</div>

<!-- Appointment select modal -->  

<div class="modal fade" id="appointmentselectModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog appointmentselect-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <div>

                    <h2>Select Brand</h2>

                    <div class="input-group">

                        <input type="text" class="form-control search-brand-input" id="search_brand" placeholder="Search Brand"  aria-label="Amount (to the nearest dollar)">

                        <div class="search-icon">

                            <i class="fa-solid fa-magnifying-glass"></i>

                        </div>

                    </div>

                    <div class="row m-0" id="amodal_brands">

                        @php($brands = getbrands())

                        @if($brands->count())

                            @foreach($brands as $brand)

                                @if($brand->image)

                                    <div class="col-4 brand-logo-center">

                                        <a href="javascript:void(0);" class="amodal-brand" data-id="{{$brand->id}}"><img src="{{ asset('public/uploads/carbrand/'.$brand->image) }}" class="img-fluid" alt=""></a>

                                    </div>

                                @endif

                            @endforeach

                        @endif

                    </div>

                </div>

            </div>

            <div class="modal-footer">

            </div>

        </div>

    </div>

</div>

<!-- Appointment select modal -->

<!-- Appointment search modal -->

<div class="modal fade" id="appointmentsearchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog appointmentsearch-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <div>

                    <h2>Select Model</h2>

                    <div class="input-group">

                        <input type="text" class="form-control search-brand-input" id="search_model" placeholder="Search Model"  aria-label="Amount (to the nearest dollar)">

                        <div class="search-icon">

                            <i class="fa-solid fa-magnifying-glass"></i>

                        </div>

                    </div>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#appointmentselectModal">Change</a>

                    <div class="row m-0 search-modal-box" id="amodal_models">



                    </div>

                </div>

            </div>

            <div class="modal-footer"> </div>

        </div>

    </div>

</div>

<!-- Appointment search modal -->

<!-- Appointment fuel modal -->

<div class="modal fade" id="appointmentfuelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

<div class="modal-dialog appointmentfuel-dialog">

    <div class="modal-content">

        <div class="modal-header">

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

        </div>

        <div class="modal-body">

            <div>

                <h2>Select Fuel Type </h2>

                <div class="input-group">

                    <input type="text" class="form-control search-brand-input" id="search_fuel" placeholder="Search Fuel"  aria-label="Amount (to the nearest dollar)">

                    <div class="search-icon">

                        <i class="fa-solid fa-magnifying-glass"></i>

                    </div>

                </div>

                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#appointmentselectModal">Change</a>

                <div class="row m-0 search-modal-box" id="amodal_fuels">

                </div>

            </div>

        </div>

        <div class="modal-footer"> </div>

    </div>

</div>

</div>

<!-- Appointment fuel modal -->

<!-- Appointment Number modal -->

<div class="modal fade" id="appointmentnumberModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog appointmentnumber-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <div>

                    <h2>Get instant quotes for your car service </h2>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#appointmentselectModal">Change</a>

                    <div class="row m-0 search-modal-box" id="search_info">



                    </div>

                    <div class="form-group">

                        <input type="text" class="form-control num_only" maxlength="10"  id="appointmentmobile" name="mobile" aria-describedby="emailHelp" placeholder="Enter Phone Number">

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <a class="check-price-btn-main" id="check_price" href="javascript:void(0);"><button type="button"  class="check-price-btn" >Check Price For Free </button></a>

            </div>

        </div>

    </div>

</div>

<!-- Appointment Number modal -->

@endsection

@section('javascript')

<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>

<script src="{{ asset('front/js/owl.carousel.min.js') }}"></script>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>

    $(document).ready(function(){

        $('#compnyresend_otp').hide();

        $('.compnyotp-section').hide();

        var phone = localStorage.getItem("phone");

        $('#mobile').val(phone);

        if(phone) {            

            $('#send_message').show();

            $('#compnysend_otp').hide();

        }

        else {

            $('#send_message').hide();

            $('#compnysend_otp').show();

        }



        $(document).on('keyup', '#mobile', function(){

            var validateMobNum= /[1-9]{1}[0-9]{9}/;

            var mobile = $('#mobile').val();

            if (validateMobNum.test(mobile) && mobile.length == 10) {

                var verified_mobile = localStorage.getItem("phone");

                if(verified_mobile != mobile){

                    $('#send_message').hide();

                    $('#compnysend_otp').show();

                } else {

                    $('#send_message').show();

                    $('#compnysend_otp').hide();

                }

            }

        });

        $(document).on('click', '#compnysend_otp', function(){

            var validateMobNum= /[1-9]{1}[0-9]{9}/;

            var mobile = $('#mobile').val();

            if (validateMobNum.test(mobile) && mobile.length == 10) {

                var gresponse = grecaptcha.getResponse();

                if(gresponse!="") {

                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

                    $.ajax({

                        url : '{{ route('front_send-otp') }}',

                        method : 'post',

                        data : {_token: CSRF_TOKEN, mobile:mobile, gresponse:gresponse},

                        success : function(result){

                            var result = $.parseJSON(result);

                            if(result.result == 'success'){

                                $("#mobile").attr("readonly", "readonly");

                                $('.compnyotp-section').show();

                                $('#compnysend_otp').hide();

                                timer(30);

                            } else {

                                toastr.error('Something went wrong. Please try again later!');

                            }

                        }

                    });

                } else {

                    toastr.error('Please complete the captcha.');

                }

            }

            else {

                toastr.error('Please Enter Valid Mobile No.');

            }

        });



        $(document).on('keyup', '#compnyotp', function(){

            var mobile = $('#mobile').val();

            var otp = $('#compnyotp').val();

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

                            $('#compnyresend_text').hide();

                            $('#compnyis_otp_verify').val('1');

                            $('#send_message').show();

                            $("#mobile").attr("readonly", "readonly"); 

                            $('#compnyotp').hide();

                        } else {

                            toastr.error('Please Enter Valid OTP.');

                        }

                    }

                });

            }

        });



        $(document).on('click', '#compnyresend_otp', function(){

            var validateMobNum= /[1-9]{1}[0-9]{9}/;

            var mobile = $('#mobile').val();

            if (validateMobNum.test(mobile) && mobile.length == 10) {

                var gresponse = grecaptcha.getResponse();

                if(gresponse!="") {

                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

                    $.ajax({

                        url : '{{ route('front_resend-otp') }}',

                        method : 'post',

                        data : {_token: CSRF_TOKEN, mobile:mobile, gresponse:gresponse},

                        success : function(result){

                            var result = $.parseJSON(result);

                            if(result.result == 'success'){

                                console.log('test');

                                $('.compnyotp-section').show();

                                $('#compnyresend_text').show();

                                $('#compnyotp').val('');

                                $('#compnyotp').show();

                                $("#mobile").attr("readonly", "readonly");

                                $('#compnyresend_otp').hide();

                                timer(30);

                            } else {

                                toastr.error('Something went wrong. Please try again later!');

                            }

                        }

                    });

                } else {

                    toastr.error('Please complete the captcha.');

                }

            }

            else {

                toastr.error('Please Enter Valid Mobile No.');

            }

        });

    });

    let timerOn = true;

            function timer(remaining) {

                var m = Math.floor(remaining / 60);

                var s = remaining % 60;

                m = m < 10 ? '0' + m : m;

                s = s < 10 ? '0' + s : s;

                document.getElementById('compnytimer').innerHTML = m + ':' + s;

                remaining -= 1;

                if(remaining >= 0 && timerOn) {

                setTimeout(function() {

                    timer(remaining);

                }, 1000);

                return;

                }



                if(!timerOn) {

                // Do validate stuff here

                return;

                }

                // Do timeout stuff here

                var is_otp_verify = $('#compnyis_otp_verify').val();

                if(is_otp_verify == '0'){

                    $('#compnyresend_otp').show();

                    $("#mobile").removeAttr("readonly"); 

                    $('#compnyresend_text').hide();

                    $('#compnyotp').hide();

                }

            }

    </script>

@endsection