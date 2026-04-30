@extends('front.layout.main')

@section('content')


<section class="page-header page-header--details">
    <div class="page-header__bg"></div>
    <div class="container">
        <h1 class="page-header__title bw-split-in-right">{{ strtoupper($tabServiceInfo->name) }}</h1>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{url('/')}}"><i class="flaticon-home"></i>Home</a></li>
            <li><span>Our Services</span></li>
            <li><span>{{$tabServiceInfo->name}}</span></li>
        </ul>
    </div>
</section>

<section class="service-details">
    <div class="container">
        <div class="row gutter-y-60">
            

            <div class="col-md-12 col-lg-4">
                <div class="service-sidebar">
                    <div class="service-sidebar__single">
                        <ul class="list-unstyled service-sidebar__nav">
                            @foreach($service_list as $service)
                            <li class="@if(Request::segment(1) == $service->slug){{'active'}}@endif">
                                <a href="{{url($service->slug)}}">{{$service->name}}</a>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <!--side form start  -->
                                        
                    <!-- <style>
                         Container styles 
                        
                        
                        @keyframes fadeIn-csb {
                            to {
                                opacity: 1;
                                transform: scale(1);
                            }
                        }
                        
                         Card styles 
                        .card-csb {
                            background-color: white;
                            
                            overflow: hidden;
                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                            opacity: 0;
                            animation: slideUp-csb 0.5s forwards 0.2s;
                            margin: 20px 0;
                        }
                        
                        @keyframes slideUp-csb {
                            to {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }
                        
                        .card-header-csb {
                            background: linear-gradient(to right, rgb(226 59 51), rgb(229 113 38));
                            padding: 1rem;
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                        }
                        
                        .card-header-csb h3 {
                            color: white;
                            font-size: 1.5rem;
                            font-weight: 700;
                        }
                        
                        .card-header-csb i {
                            color: white;
                            font-size: 1.5rem;
                        }
                        
                         Form styles 
                        .form-container-csb {
                            padding: 1rem 1.5rem;
                        }
                        
                        .form-group-csb {
                            margin-bottom: 1rem;
                            opacity: 0;
                            transform: translateY(20px);
                        }
                        
                        .form-label-csb {
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                            margin-bottom: 0.5rem;
                            font-weight: 500;
                            color: #374151;
                        }
                        
                        .form-label-csb i {
                            color: #E23B33;
                        }
                        
                        .form-control-csb {
                            width: 100%;
                            padding: 0.50rem 1rem;
                            border: 1px solid #d1d5db;
                            border-radius: 0.5rem;
                            font-size: 1rem;
                            transition: all 0.3s ease;
                        }
                        
                        .form-control-csb:focus {
                            outline: none;
                            border-color: #E23B33;
                            box-shadow: 0 0 0 3px rgba(246, 59, 59, 0.25);
                        }
                        
                        .form-select-csb {
                            width: 100%;
                            padding: 0.75rem 1rem;
                            border: 1px solid #d1d5db;
                            border-radius: 0.5rem;
                            font-size: 1rem;
                            background-color: white;
                            appearance: none;
                            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23374151'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
                            background-repeat: no-repeat;
                            background-position: right 1rem center;
                            background-size: 1rem;
                        }
                        
                        .form-select-csb:focus {
                            outline: none;
                            border-color: #E23B33;
                            box-shadow: 0 0 0 3px rgba(246, 59, 59, 0.25);
                        }
                        
                        .checkbox-container-csb {
                            display: flex;
                            align-items: flex-start;
                            gap: 0.75rem;
                            margin-top: 0.5rem;
                        }
                        
                        .checkbox-container-csb input[type="checkbox"] {
                            margin-top: 0.25rem;
                        }
                        
                        .checkbox-container-csb label {
                            font-size: 0.875rem;
                            color: #4b5563;
                            line-height: 1.4;
                        }
                        
                        .recaptcha-container-csb {
                            display: flex;
                            justify-content: center;
                            margin: 1.5rem 0;
                            transform: scale(0.85);
                            transform-origin: center;
                        }
                        
                         Button styles 
                        
                        
                         Loading spinner 
                        .spinner-csb {
                            display: none;
                            width: 20px;
                            height: 20px;
                            border: 3px solid rgba(255, 255, 255, 0.3);
                            border-radius: 50%;
                            border-top-color: white;
                            animation: spin-csb 1s ease-in-out infinite;
                            margin-right: 0.5rem;
                        }
                        
                        @keyframes spin-csb {
                            to {
                                transform: rotate(360deg);
                            }
                        }
                        
                         Animations for form elements 
                        @keyframes fadeInUp-csb {
                            to {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }
                        
                         Apply animations to form groups with delay 
                        .form-group-csb:nth-child(1) {
                            animation: fadeInUp-csb 0.5s forwards 0.3s;
                        }
                        
                        .form-group-csb:nth-child(2) {
                            animation: fadeInUp-csb 0.5s forwards 0.4s;
                        }
                        
                        .form-group-csb:nth-child(3) {
                            animation: fadeInUp-csb 0.5s forwards 0.5s;
                        }
                        
                        .form-group-csb:nth-child(4) {
                            animation: fadeInUp-csb 0.5s forwards 0.6s;
                        }
                        
                        .form-group-csb:nth-child(5) {
                            animation: fadeInUp-csb 0.5s forwards 0.7s;
                        }
                        
                        .form-group-csb:nth-child(6) {
                            animation: fadeInUp-csb 0.5s forwards 0.8s;
                        }
                        
                        .form-group-csb:nth-child(7) {
                            animation: fadeInUp-csb 0.5s forwards 0.9s;
                        }
                        
                         Responsive styles 
                        @media (max-width: 480px) {
                            .card-header-csb h3 {
                                font-size: 1.25rem;
                            }
                            
                            .form-container-csb {
                                padding: 1.25rem;
                            }
                            
                            .recaptcha-container-csb {
                                transform: scale(0.77);
                            }
                        }
                        @media (max-width: 768px) {
                                        .service-sidebar__single1 {
                                            display: none;
                                        }
                                    }
                    </style> -->

                        <!-- Form Card 
                        <div class="card-csb" id="form-card-csb">
                            <div class="card-header-csb">
                                <i class="fas fa-car"></i>
                                <h3>Book Car Service</h3>
                            </div>
                            
                            <div class="form-container-csb">
                                <form id="booking-form-csb">
                                    <div class="form-group-csb">
                                        <label class="form-label-csb" for="name-csb">
                                            <i class="fas fa-user"></i>
                                            Full Name
                                        </label>
                                        <input type="text" class="form-control-csb" id="name-csb" name="name" placeholder="Enter your name" required>
                                    </div>
                                    
                                    <div class="form-group-csb">
                                        <label class="form-label-csb" for="email-csb">
                                            <i class="fas fa-envelope"></i>
                                            Email Address
                                        </label>
                                        <input type="email" class="form-control-csb" id="email-csb" name="email" placeholder="Enter your email" required>
                                    </div>
                                    
                                    <div class="form-group-csb">
                                        <label class="form-label-csb" for="phone-csb">
                                            <i class="fas fa-phone"></i>
                                            Phone Number
                                        </label>
                                        <input type="tel" class="form-control-csb" id="phone-csb" name="phone" placeholder="Enter your phone number" maxlength="10" required>
                                    </div>
                                    
                                    <div class="form-group-csb">
                                        <label class="form-label-csb" for="location-csb">
                                            <i class="fas fa-map-marker-alt"></i>
                                            Service Location
                                        </label>
                                        <select class="form-select-csb" id="location-csb" name="location" required>
                                            <option value="" disabled selected>Select Location</option>
                                            <option value="Motinagar">Motinagar</option>
                                            <option value="Gurgaon">Gurgaon</option>
                                            <option value="Noida">Noida</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group-csb">
                                        <div class="checkbox-container-csb">
                                            <input type="checkbox" id="consent-csb" name="consent" required>
                                            <label for="consent-csb">
                                                I agree to receive calls, e-mails, WhatsApp messages, and SMS from ACR.
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group-csb">
                                        <div class="recaptcha-container-csb">
                                            <div class="g-recaptcha" data-sitekey="YOUR_RECAPTCHA_KEY"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-one__control form-one__control--full">
                                        <button type="submit" class="karoons-btn"><span><i class="fi fi-rr-arrow-up-right"></i>Submit</span></button>
                                    </div>
                                </form>
                            </div>
                        </div> -->

                    <!-- side form end  -->

                    <div class="service-sidebar__single1">
                        <div class="service-sidebar__contact" style="background-image: url(front/images/resources/service-contact-bg.jpg);">
                            
                            <div class="service-sidebar__contact__info">
                                <p class="service-sidebar__contact__number">
                                    <span>Get In Touch</span>
                                    <a href="tel:{{$phone}}"> <i class="fa-solid fa-phone"></i> {{$phone}}</a>
                                </p>
                                <p class="service-sidebar__contact__number">
                                    <a href="mailto:{{$email}}"><i class="fa-solid fa-paper-plane"></i> {{$email}}</a>
                                </p>
                                
                            </div>
                            
                        </div>
                    </div>
                    
                    @if(isset($tabServiceInfo->brochure) && $tabServiceInfo->brochure != '')
<div class="brochure-download-container">
    <a href="{{ asset('uploads/tabservicecms/brochures/'.$tabServiceInfo->brochure) }}" class="brochure-download-btn" download>
        <span class="btn-text">Download Brochure</span>
        <span class="btn-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 9L12 2L5 9H3V21H19V9ZM14 14V12H16V18H14V14ZM12 12V10H10V16H12V12Z" fill="currentColor"/>
            </svg>
        </span>
    </a>
</div>
@endif
<style>
            .brochure-download-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .brochure-download-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background-color: #E23B33;
            color: #fff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .brochure-download-btn .btn-text {
            margin-right: 10px;
        }

        .brochure-download-btn .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .brochure-download-btn:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .brochure-download-btn:hover .btn-icon {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .brochure-download-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300%;
            height: 300%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%) rotate(45deg);
            transition: all 0.5s ease;
            z-index: -1;
        }

        .brochure-download-btn:hover::before {
            width: 400%;
            height: 400%;
        }

</style>               
                </div>
            </div>
            <style>
                @media (max-width: 768px) {
                    .service-sidebar__single1 {
                        display: none;
                    }
                }
                @media (max-width: 768px) {
                    .card-csb {
                        display: none;
                    }
                }
            </style>

            <div class="col-md-12 col-lg-8">
                <div class="service-details__content">
                    <div class="service-details__thumbnail">
                        <img src="{{$banner_image}}" alt="{{$tabServiceInfo->name}}" title="{{$tabServiceInfo->image_title}}">
                    </div>
                   
                    <h2 class="heading_cms_page">{{ strtoupper($tabServiceInfo->name) }}</h2>
                    <div class="tab-service-content">
                        {!! $tabServiceInfo->description !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-one cta-one--service-details">
    <div class="cta-one__bg jarallax" data-jarallax data-speed="0.3" data-imgPosition="50% -100%" style="background-image: url(front/images/backgrounds/cta-one--service-details.jpg);"></div>
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center wow fadeInUp">
                <div class="cta-one__content">
                    <div class="sec-title text-left">
                        <h6 class="sec-title__tagline bw-split-in-right">Get Discount<span class="sec-title__tagline__border"></span></h6>
                        <h3 class="sec-title__title bw-split-in-left">Get a <span>30% discount</span> on car<br> diagnostics</h3>
                    </div>
                    <a href="https://autocarrepair.in/offer" class="karoons-btn"><span><i class="fa-solid fa-arrow-up-right-from-square"></i>
                    Get A Quote</span></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- New Contact Form Section -->
<section class="contact-one" style="background-image: url(front/images/shapes/contact-bg-1.png);">
    <div class="contact-one__bg wow slideInRight" data-wow-delay="100ms" style="background-image: url(front/images/backgrounds/contact-bg-1--team-details.jpg);"></div>
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
<form class="contact-one__form contact-form-validated form-one wow fadeInUp ajax-form"  method="POST" 
      action="{{ route('enquiry.submit') }}"  id="compny-form" enctype="multipart/form-data">
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
                            <option value="" disabled selected>Select Location</option>
                            <option value="ACR Motinagar">Motinagar</option>
                            <option value="ACR Gurgaon">Gurgaon</option>
                            <option value="ACR Noida">Noida</option>
                            <option value="ACR Okhla">Okhla</option>
                        </select>
                    </div>
            
                    <div class="form-one__control">
                        <input type="tel" name="phone" id="phone_number" required maxlength="10" placeholder="Enter Your Phone Number" pattern="\d{10}" title="Please enter a 10-digit phone number">
                    </div>
             <!-- HONEYPOT FIELDS (hidden from users, visible to bots) -->
        <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
            <input type="url" name="url" tabindex="-1" autocomplete="off">
        </div>
                    <!-- REMOVED INLINE PHONE SCRIPT - HANDLED BY UNIVERSAL SCRIPT -->
                    
                    <div class="form-one__control form-one__control--full">
                        <label for="message">Write Message</label>
                        <textarea id="message" name="message" placeholder=""></textarea>
                    </div>
            
                    <!-- Hidden UTM fields -->
                    <input type="hidden" name="utm_source" value="">
                    <input type="hidden" name="utm_medium" value="">
                    <input type="hidden" name="utm_campaign" value="">
                    <input type="hidden" name="utm_term" value="">
                    <input type="hidden" name="utm_content" value="">
            
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
                </div>
            </form>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var numberInput = document.getElementById("phone_number");
    numberInput.addEventListener('input', function () {
        var inputValue = numberInput.value.replace(/\D/g, '');
        var slicedValue = inputValue.slice(0, 10);
        numberInput.value = slicedValue;
    });
});

$(document).ready(function(){
    // Your existing OTP and form validation JavaScript here
    // Keep all the JavaScript from your old layout
});
</script>
@endsection
