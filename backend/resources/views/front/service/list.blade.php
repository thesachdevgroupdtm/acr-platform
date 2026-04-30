@extends('front.layout.main')

@section('content')
 <style>
    @media (max-width: 768px) { /* Adjust width if needed */
    .secondimg {
        display: none;
    }
}

 </style>
<section class="page-header">
            <div class="page-header__bg"></div>
            <!-- /.page-header__bg -->
            <div class="container">
                <h1 class="page-header__title bw-split-in-right">Our Services</h1>
                <ul class="karoons-breadcrumb list-unstyled">
                    <li><a href="index.html"><i class="flaticon-home"></i>Home</a></li>
                    <li><span>Services</span></li>
                </ul><!-- /.thm-breadcrumb list-unstyled -->
            </div><!-- /.container -->
        </section><!-- /.page-header -->

        <section class="service-about">
            <div class="container">
                <div class="row gutter-y-30">
                    <div class="col-lg-7">
                        <div class="sec-title text-left">

                            <h6 class="sec-title__tagline bw-split-in-right">our services<span class="sec-title__tagline__border"></span></h6><!-- /.sec-title__tagline -->

                            <h3 class="sec-title__title bw-split-in-left">Reliable and quality car repair services</h3><!-- /.sec-title__title -->
                        </div><!-- /.sec-title -->
                        <div class="service-about__image">
                            <img src="front/images/resources/service-about-1.jpg" alt="karoons">
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="service-about__image secondimg">
                            <img src="front/images/resources/service-about-2.jpg" alt="karoons">
                        </div>
                        <p class="service-about__text">
                            <strong>Luxury car owners in Delhi NCR</strong> trust Auto Car Repair for <strong>factory-approved servicing</strong> without dealership premiums. We specialize in <strong>German engineering</strong> (Audi/BMW/Mercedes) with <strong>STAR-certified diagnostics</strong>, <strong><a href="https://autocarrepair.in/ceramic-coating-delhi" class="no-style-link" rel="noopener noreferrer"
   title="ceramic coating"> ceramic coating </a></strong>, and <strong>insurance-friendly repairs</strong>.  
                            <br>  
                            <strong>📍 Noida | Gurugram | Okhla | South Delhi</strong>  
                        </p>
                        <div class="usp-bar">
                            <span>🔧 2000+ Cars Serviced</span> 
                            <span>⭐ 4.9/5 (Google Reviews)</span>  
                            <span>🏆 Luxury Car Specialists</span>  
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="product">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        
                        <div class="row gutter-y-30">
                            @if(isset($scategories) && $scategories->count())
                            @foreach($scategories as $service)
                                <div class="col-6 col-md-4 col-lg-3" id="car_services">
                                @php($href = $service->slug)
                                @if(isset($brand) && isset($model) && isset($fuel))
                                @php($href = $service->slug.'/'.$brand.'/'.$model.'/'.$fuel)
                                @endif
                                    <div class="product__item wow fadeInUp" data-wow-duration='1500ms' data-wow-delay='000ms'  href="{{url($href)}}">
                                        @if(!empty($service->icon_image) && isset($service->icon_image))
                                            <div class="product__item__img">
                                                <img src="{{url('uploads/service/category/icon/'.$service->icon_image)}}" alt="service">
                                            </div>
                                            <div class="product__item__content">
                                                <h4 class="product__item__title"><a href="{{url($href)}}">{{ $service->title }}</a></h4>
                                            </div>
                                        @endif 
                                    </div>
                                </div>
                            @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Service End -->


        <section class="contact-one" style="background-image: url(front/images/shapes/contact-bg-1.png);">
            <div class="contact-one__bg wow slideInRight" data-wow-delay="100ms" style="background-image: url(front/images/backgrounds/contact-bg-1--team-details.jpg);"></div>
            <div class="container">
                <div class="row">
                    <div class="col-lg-8">
<form class="contact-one__form contact-form-validated form-one wow fadeInUp"  method="POST" 
      action="{{ route('enquiry.submit') }}" id="compny-form" enctype="multipart/form-data" data-parsley-validate="">
                 @csrf
                <div class="contact-one__form__dot-one"></div>
                <div class="contact-one__form__dot-two"></div>
                <div class="contact-one__form__dot-three"></div>
                <div class="contact-one__form__dot-four"></div>
            
                <div class="sec-title text-left">
                    <h6 class="sec-title__tagline bw-split-in-right">Contact Us<span class="sec-title__tagline__border"></span></h6>
                    <h3 class="sec-title__title bw-split-in-left">Have Questions? <br>Get In <span>Touch!</span></h3>
                </div>
            
                <div class="form-one__group">
                    <div class="form-one__control">
                        <input id="first-name" type="text" name="name" required placeholder="Enter Your Name">
                    </div>
            
                    <div class="form-one__control">
                        <input id="email" type="email" name="email" required placeholder="Enter Your Email">
                    </div>
            
                    <div class="form-one__control">
                        <select id="location" name="location" style="background-color: #F4F4F4; color: #838383; height: 50px; width: 100%; padding: 0 30px; border: none;" required>
                            <option selected disabled>Select Location</option>
                            <option value="ACR Motinagar">Motinagar</option>
                            <option value="ACR Gurgaon">Gurgaon</option>
                            <option value="ACR Noida">Noida</option>
                            <option value="ACR Okhla">Okhla</option>
                        </select>
                    </div>
            
                    <div class="form-one__control">
                        <input type="tel" name="phone" id="phone_number" required maxlength="10" placeholder="Enter Your Phone Number" pattern="\d{10}" title="Please enter a 10-digit phone number">
                    </div>
            
                    <!-- Phone Number Restriction Script -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var numberInput = document.getElementById("phone_number");
                            numberInput.addEventListener('input', function () {
                                var inputValue = numberInput.value.replace(/\D/g, '');
                                numberInput.value = inputValue.slice(0, 10);
                            });
                        });
                    </script>
            
                    
             <!-- HONEYPOT FIELDS (hidden from users, visible to bots) -->
        <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
            <input type="url" name="url" tabindex="-1" autocomplete="off">
        </div>
                    <!-- Hidden UTM fields -->
                    <input type="hidden" name="utm_source" value="">
                    <input type="hidden" name="utm_medium" value="">
                    <input type="hidden" name="utm_campaign" value="">
                    <input type="hidden" name="utm_term" value="">
                    <input type="hidden" name="utm_content" value="">
            
                    <!-- Message container -->
                         <div id="form-messages" style="display: none; margin-bottom: 15px; padding: 10px; border-radius: 4px;"></div>
            
                    <!-- Captcha -->
                        <div class="form-one__control">
                            <div class="captcha-container">
                                <label class="captcha-question">Loading captcha...</label>
                                <input type="text" class="captcha-answer" name="captcha" style="width: 60%;" placeholder="Enter answer" required>
                                <input type="hidden" class="correct-answer" name="correct_answer">
                            </div>
                    </div>
                        
                        <div class="form-one__control form-one__control--full">
                            <div class="agree-line">
                                <input type="checkbox" name="agree" required>
                                <label>I agree to receive calls, e-mail, WhatsApp messages, and SMS from ACR.</label>
                            </div>
                        </div>
                        
                        <div class="form-one__control form-one__control--full">
                            <button type="submit" class="karoons-btn">
                                <span><i class="fi fi-rr-arrow-up-right"></i>Submit</span>
                            </button>
                        </div>

                    </div><!-- /.col-lg-8 -->
</form>
                    </div><!-- /.col-lg-8 -->
                </div>
            </div><!-- /.container -->
        </section><!-- /.contact-one -->




@endsection


