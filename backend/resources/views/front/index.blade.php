@extends('front.layout.main')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Your content goes here -->
<section class="main-slider-four">

    <div class="main-slider-four__carousel karoons-owl__carousel owl-carousel"
        data-owl-options='{
            "loop": true,
            "animateOut": "fadeOut",
            "animateIn": "fadeIn",
            "items": 1,
            "autoplay": false,
            "autoplayTimeout": 7000,
            "smartSpeed": 1000,
            "nav": false,
            "dots": true,
            "margin": 0
        }'>

        @if(!empty($hsetting->section1_image))
            @foreach($hsetting->section1_image as $image)
             <div class="item">
    <div class="main-slider-four__item">

        {{-- AUTO HEIGHT IMAGE --}}
        <img
            class="main-slider-four__auto-bg"
            src="{{ asset('uploads/content/'.$image) }}"
            alt="Slider Background"
            loading="eager">

     

    </div>
</div>

            @endforeach
        @endif

    </div>

</section>

<!-- main-slider-end -->

<section class="about-three" id="about">
    <div class="container">
        <div style="display: flex;justify-content: center;align-content: center;align-items: center;">
            <!-- Image Section - Left -->
            <div class="col-xl-6">
                        <div class="about-three__image wow fadeInLeft" data-wow-delay="100ms">
                            <div class="about-three__image__one">
                                <img src="front/images/resources/about-3-1.webp" alt="car repair" loading="lazy">
                            </div>
                            <div class="about-three__image__two">
                                <img src="front/images/resources/about-3-2.webp" alt="nearest mechanic" loading="lazy">
                            </div>
                            <div class="about-three__image__bar"></div>
                        </div><!-- /.about-three__image -->
                    </div><!-- /.col-lg-6-->
            <!-- Text Section - Right -->
           <div class="col-lg-8 wow fadeInUp" data-wow-delay="300ms">
    <div class="why-choose-two__content">
        <div class="why-choose-two__content__bg" style="background-image: url(front/images/shapes/why-choose-3-shape-1.webp);"></div>
        <div class="sec-title text-left">
            <h6 class="sec-title__tagline bw-split-in-right">About us?<span class="sec-title__tagline__border"></span></h6>
            <h1 class="sec-title__title bw-split-in-left">why <span>choose</span> us</h1>
        </div>
        <p class="why-choose-two__content__text">
            <strong>Auto Car Repair (Powered by Autogine Services) </strong> is a leading network of multi-brand <a href="https://autocarrepair.in/our-services">car service</a> workshops in Delhi. With its specialized collision and accidental repair services, Proudly serving the NCR region, it offers  <strong>comprehensive, expert, and cost-effective automotive solutions</strong> for both luxury and mainstream vehicles.
        </p>
        <ul class="why-choose-two__list">
            <li><i class="fi fi-rr-check"></i>Expertise in multi-brand servicing from premium to everyday vehicles</li>
            <li><i class="fi fi-rr-check"></i>Dedicated collision & accidental repair facility with advanced equipment</li>
            <li><i class="fi fi-rr-check"></i>Genuine OES/OEM parts to ensure safety and performance</li>
            <li><i class="fi fi-rr-check"></i>Dealership-grade quality at competitive prices</li>
        </ul>
        <p class="why-choose-two__content__text" >
            If you're looking for a trusted <a href="https://autocarrepair.in/service-center">car workshop in Delhi NCR</a> that provides reliable <a href="https://autocarrepair.in/our-services">car service</a>, repairs, and detailing, visit Auto Car Repair!
        </p>
        <div class="why-choose-two__info-wrapper">
            <a href="{{url('about-us')}}" class="karoons-btn"><span><i class="fi fi-rr-arrow-up-right"></i>About Us</span></a>
            <div class="why-choose-two__info">
                <div class="why-choose-two__info__icon"><i class="fi fi-rr-phone-call"></i></div>
                <div class="why-choose-two__info__content">
                    <p class="why-choose-two__info__title" style="color:Black;">call for book</p>
                    <h3 class="why-choose-two__info__number"><a href="tel:9870400861">9870400861</a></h3>
                </div>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
</section>

<div class="client-carousel">
    <div class="container">
        <div class="client-carousel__one karoons-owl__carousel owl-theme owl-carousel" data-owl-options='{
            "items": 5,
            "margin": 65,
            "smartSpeed": 700,
            "loop":true,
            "autoplay": 6000,
            "nav":false,
            "dots":false,
            "navText": ["<span class=\"fa fa-angle-left\"></span>","<span class=\"fa fa-angle-right\"></span>"],
            "responsive":{
                "0":{
                    "items":1,
                    "margin": 0
                },
                "360":{
                    "items":3,
                    "margin": 10
                },
                "575":{
                    "items":3,
                    "margin": 30
                },
                "768":{
                    "items":4,
                    "margin": 40
                },
                "992":{
                    "items": 5,
                    "margin": 40
                },
                "1200":{
                    "items": 6,
                    "margin": 80
                }
            }
        }'>
            @if(!empty($tabular_offers))
                @foreach($tabular_offers as $tab)
                    @php
                        $imageSrc = isset($tab->image_url) && $tab->image_url ? asset('uploads/tabularoffer/'.$tab->image_url) : asset('front/img/slider-image.png');
                        $altText = isset($tab->image_url) && $tab->image_url
                            ? ucwords(str_replace(['-', '_'], ' ', pathinfo($tab->image_url, PATHINFO_FILENAME)))
                            : 'Banner Image';
                    @endphp
                    <div class="client-carousel__one__item">
                            <a href="{{ $tab->link }}" class="feature-five__item__link">
                                <div class="feature-five__item__image">
                                    <img src="{{ $imageSrc }}" class="img-fluid" alt="{{ $altText }}" title="{{ $tab->title }}" loading="lazy">
                                </div>
                                <h5 class="feature-five__item__title">{{ $tab->title }}</h5>
                            </a>
                    
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>


<!-- Feature End -->
<section class="cta-five">
    <div class="container">
        <div class="row gutter-y-30" id="offerContainer">
            @foreach($membership_package as $index => $package)
                <div class="col-lg-6 offer-item" data-index="{{ $index }}" style="display: {{ $index < 2 ? 'block' : 'none' }};">
                    <div class="cta-five__item" style="background:{{ $package->background }};">
                        <h3 class="cta-five__item__sub-title" style="color:{{ $package->title_color }}">{{ $package->title1 }}</h3>
                        <h2 class="cta-five__item__title" style="color:{{ $package->subtitle_color }}">{{ $package->title2 }}</h2>
                        <p class="cta-five__item__text">🔧Premium Car Care, Just a Step Away</p>
                        <a href="{{ $package->btn_link }}" target="_blank" class="cta-five__item__rm">
                            <span>{{ $package->btn_title }}</span>
                            <i class="fi fi-rr-arrow-right"></i>
                        </a>
                        <div class="cta-five__item__image">
                            <a href="{{ $package->image_url }}" target="_blank" aria-label="View offer details">
                                <img src="{{ asset('uploads/offerslider/'.$package->image) }}" alt="" role="presentation" loading="lazy">
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        let offers = document.querySelectorAll(".offer-item");
        let totalOffers = offers.length;
        let currentIndex = 0;
        function updateOffers() {
            let screenWidth = window.innerWidth;
            let visibleCount = screenWidth < 768 ? 1 : 2; // Show 1 on mobile, 2 on desktop
            // Hide all offers
            offers.forEach((offer) => (offer.style.display = "none"));
            // Show required number of offers
            for (let i = 0; i < visibleCount; i++) {
                let nextIndex = (currentIndex + i) % totalOffers;
                offers[nextIndex].style.display = "block";
            }
            // Update index
            currentIndex = (currentIndex + visibleCount) % totalOffers;
        }
        // Change offers every 5 seconds
        setInterval(updateOffers, 5000);
        // Run on load
        updateOffers();
        // Adjust when window resizes
        window.addEventListener("resize", updateOffers);
    });
</script>
<!-- Service Start -->
<section class="service-three">
    <div class="service-three__bg"></div>
    <div class="service-three__shape-one" style="background-image: url(front/images/resources/service-3-shape-1.webp);"></div>
    <div class="service-three__shape-two" style="background-image: url(front/images/shapes/service-3-shape-2.webp);"></div>
    <div class="service-three__shape-three" style="background-image: url(front/images/shapes/service-3-shape-3.webp);"></div>
    <div class="container">
        <div class="row">
            <!-- Service list column - order will be changed via CSS for mobile -->
            <div class="col-lg-6 service-list">
                <div class="service-list__header">
                    <h6 class="sec-title__tagline bw-split-in-right">OUR SERVICES <span class="sec-title__tagline__border"></span></h6>  
                </div>
                <div class="scrollable-container">
                    @if($scategories->count())
                        @foreach($scategories as $sk => $service)
                            @php($sslug = getDefualtServiceSlug())
                            <div class="service-item">
                                <div class="service-item__icon">
                                    @if(!empty($service->icon_image) && isset($service->icon_image))
                                        <img src="{{ url('uploads/service/category/icon/'.$service->icon_image) }}" alt="{{ $service->title }}" loading="lazy">
                                    @else
                                        <img src="{{ asset('front/img/no_image.jpg') }}" alt="{{ $service->title }}" loading="lazy">
                                    @endif
                                </div>
                                <div class="service-item__content">
                                    <h4>{{ $service->title }}</h4>
                                    <p>{{ renderExcerpts($service->description, $wordCount=8) }}</p>
                                    <a href="{{ url($service->slug.'/'.$sslug) }}" class="inner__rm">
                                        <i class="fi fi-rr-arrow-up-right"></i> Service Details
                                    </a>
                                </div>
                            </div>
                        @endforeach
                        @endif
                    </div>
                </div>
                <!-- Service image column - order will be changed via CSS for mobile -->
                <div class="col-lg-6 d-flex align-items-center wow fadeInRight service-image" data-wow-delay="300ms">
                    <div class="service-three__content">
                        <div class="sec-title text-left">
                            <h2 class="sec-title__title bw-split-in-left">Reliable and quality <br>
                                <span>car repair</span> services
                            </h2>
                        </div>
                        <img src="front/images/shapes/service-image-3.webp" alt="all part repair" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <style>
      /* ========== Mobile First Styles ========== */
      @media (max-width: 1000px) {
      .testimonials-one .row {
        display: flex;
        flex-direction: column-reverse !important;
      }
    }
      @media (max-width: 991.98px) {
        .container .row {
          display: flex;
          flex-direction: column;
        }
        .col-lg-6.service-list {
          order: 2;
        }
        .col-lg-6.service-image {
          order: 1;
          margin-bottom: 30px;
        }
        .service-three__content {
          display: flex;
          flex-direction: row;
          align-items: center;
          flex-wrap: wrap;
          justify-content: space-between;
          margin-top: 0;
        }
        .service-three__content .sec-title {
          flex: 1;
          min-width: 280px;
          margin-right: 15px;
        }
        .service-three__content img {
          flex: 1;
          min-width: 200px;
          max-width: 50%;
          height: auto;
          max-height: 300px;
          object-fit: contain;
        }
        .service-list,
        .service-image {
          margin-bottom: 20px;
        }
        .service-three {
          scroll-margin-top: 80px;
        }
      }
      /* ========== Extra Small Screens ========== */
      @media (max-width: 575px) {
        .service-three__content {
          flex-direction: column;
          align-items: flex-start;
        }
        .col-lg-6.service-image {
          margin-bottom: 3px;
        }
        .faq-one {
            display:none;
        }
        .service-three__content .sec-title {
          margin-right: 0;
          width: 100%;
        }
        .service-three__content img {
          max-width: 100%;
          display:none;
        }
      }
      /* ========== General Styles ========== */
      .service-list {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        z-index: 1;
      }
      .scrollable-container {
        max-height: 450px;
        overflow-y: auto;
        padding-right: 1px;
      }
      .service-item {
        display: flex;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #eaeaea;
        transition: background-color 0.3s;
      }
      .service-item:hover {
        background-color: #f1f1f1;
      }
      .service-item__icon {
        margin-right: 20px;
      }
      .service-item__icon img {
        width: 50px;
        height: 50px;
      }
      .service-item__content h4 {
        margin: 0 0 5px;
        font-size: 18px;
      }
      .service-item__content p {
        margin: 0 0 5px;
        font-size: 14px;
        color: #666;
      }
      .service-image img {
        max-width: 100%;
        height: auto;
      }
    </style>
    <!-- /.portfolio-one -->
    <section class="faq-one faq-one--home-four">
        <div class="container">
            <h2 class="faq-one--home-four__sec-title">Frequently Asked Questions</h2>
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="faq-scrollable-container-home"> <!-- Scrollable container -->
                        <div class="faq-one__accordion karoons-accrodion" data-grp-name="karoons-accrodion">
                            @if($faqs->count())
                                @foreach($faqs as $key => $faq)
                                    <div class="accrodion {{ $key == 0 ? 'active' : '' }}">
                                        <div class="accrodion-title">
                                            <h4>
                                                {{ $faq->name }}
                                                <span class="accrodion-title__icon"></span>
                                            </h4>
                                        </div>
                                        <div class="accrodion-content" style="{{ $key == 0 ? '' : 'display: none;' }}">
                                            <div class="inner">
                                                {!! $faq->description !!}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p>No FAQs available</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.faq-one -->
    <section class="contact-one" style="background-image: url(front/images/shapes/contact-bg-1.webp);">
    <div class="contact-one__bg wow slideInRight" data-wow-delay="100ms" style="background-image: url(front/images/backgrounds/contact-bg-1.webp);"></div>
    <div class="container">
        <div class="row"> <!-- Added row for proper Bootstrap structure -->
            <div class="col-lg-8">
                <!-- Updated form template with security features -->
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
                    
            
             <input type="hidden" name="utm_source" value="google ads">
    <input type="hidden" name="utm_medium" value="cpc">
    <input type="hidden" name="utm_campaign" value="car repair service.">
    <input type="hidden" name="utm_term" value="car repair">
    <input type="hidden" name="utm_content" value="paid marketing">
    
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
            </div>
        </div> <!-- End of row -->
    </div>
</section>
    <!-- /.contact-one -->
    <div class="client-carousel">
        <div class="container">
            <div class="client-carousel__one karoons-owl__carousel owl-theme owl-carousel" data-owl-options='{
                "items": 5,
                "margin": 65,
                "smartSpeed": 700,
                "loop":true,
                "autoplay": 6000,
                "nav":false,
                "dots":false,
                "navText": ["<span class=\"fa fa-angle-left\"></span>","<span class=\"fa fa-angle-right\"></span>"],
                "responsive":{
                    "0":{
                        "items":1,
                        "margin": 0
                    },
                    "360":{
                        "items":4,
                        "margin": 10
                    },
                    "575":{
                        "items":3,
                        "margin": 30
                    },
                    "768":{
                        "items":4,
                        "margin": 40
                    },
                    "992":{
                        "items": 5,
                        "margin": 40
                    },
                    "1200":{
                        "items": 6,
                        "margin": 80
                    }
                }
            }'>
    @if($car_brands->count())
        @foreach($car_brands as $record)
            <div class="client-carousel__one__item">
                <img src="{{ asset('uploads/carbrand/'.$record->image) }}"
                     alt="{{ $record->original_image_name ?? ucwords(str_replace(['-', '_'], ' ', $record->title)) }}"
                     title="{{ $record->original_image_name ?? ucwords(str_replace(['-', '_'], ' ', $record->title)) }}"
                     loading="lazy">
            </div>
        @endforeach
    @endif
            </div>
            <!-- /.thm-owl__slider -->
        </div>
        <!-- /.container -->
    </div>
    <section class="testimonials-one">
        <div class="testimonials-one__shape" style="background-image: url(front/images/resources/testimonial-1-shape-1.webp);"></div>
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <div class="karoons-stretch-element-inside-column">
                        <div class="testimonials-one__carousel karoons-owl__carousel owl-carousel karoons-owl__carousel--custom-nav" data-owl-nav-prev=".testimonials-one__prev" data-owl-nav-next=".testimonials-one__next" data-owl-options='{
    							"items": 1,
    							"margin": 30,
    							"loop": true,
    							"smartSpeed": 700,
    							"rtl": true,
    							"nav": false,
    							"navText": ["<i class=\"flaticon-up-left-arrow\"></i>","<i class=\"fi fi-rr-arrow-up-right\"></i>"],
    							"dots": false,
    							"autoplay": false,
    							"responsive": {
    								"0": {
    									"items": 1
    								},
    								"768": {
    									"items": 1.4
    								},
    								"900": {
    									"items": 1.2
    								},
    								"1300": {
    									"items": 1.4
    								},
    								"1600": {
    									"items": 1.73
    								}
    							}
    						}'>
                            <div class="item">
                                <div class="testimonials-card wow fadeInUp" data-wow-duration='1500ms' data-wow-delay='000ms'>
                                    <div class="testimonials-card__rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="testimonials-card__content">
                                        Auto Car Repair gave my BMW perfect care in Delhi NCR. Their skilled team used genuine parts and delivered exceptional service with complete transparency. Highly recommended
                                    </p>
                                    <div class="testimonials-card__author">
                                        <div class="testimonials-card__image">
                                            <img src="front/images/resources/testi-1-1.jpg" alt="Rahul Mehta" loading="lazy">
                                        </div>
                                        <h3 class="testimonials-card__name">
                                                    Atul Tiwari
                                                </h3>
                                        <p class="testimonials-card__designation">BMW Owner</p>
                                    </div>
                                    <div class="testimonials-card__quote" style="background-image: url(front/images/shapes/quote-one.png);"></div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="testimonials-card wow fadeInUp" data-wow-duration='1500ms' data-wow-delay='100ms'>
                                    <div class="testimonials-card__rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="testimonials-card__content">
                                        My Mercedes got top-notch service at Auto Car Repair. Their Noida workshop has expert technicians who deliver dealership quality at much better prices.Truly impressive
                                    </p>
                                    <div class="testimonials-card__author">
                                        <div class="testimonials-card__image">
                                            <img src="front/images/resources/testi-1-2.jpg" alt="Priya Sharma" loading="lazy">
                                        </div>
                                        <h3 class="testimonials-card__name">
                                                    Harsh Sharma
                                                </h3>
                                        <p class="testimonials-card__designation">Mercedes Owner</p>
                                    </div>
                                    <div class="testimonials-card__quote" style="background-image: url(front/images/shapes/quote-one.png);"></div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="testimonials-card wow fadeInUp" data-wow-duration='1500ms' data-wow-delay='200ms'>
                                    <div class="testimonials-card__rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="testimonials-card__content">
                                        The dent repair on my Hyundai at Auto Car Repair Gurugram was flawless. They offered fair pricing and completed the job faster than authorized service centers
                                    </p>
                                    <div class="testimonials-card__author">
                                        <div class="testimonials-card__image">
                                            <img src="front/images/resources/testi-1-3.jpg" alt="Vikram Singh" loading="lazy">
                                        </div>
                                        <h3 class="testimonials-card__name">
                                                    Vikash Pandey
                                                </h3>
                                        <p class="testimonials-card__designation">Hyundai Owner</p>
                                    </div>
                                    <div class="testimonials-card__quote" style="background-image: url(front/images/shapes/quote-one.png);"></div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="testimonials-card wow fadeInUp" data-wow-duration='1500ms' data-wow-delay='000ms'>
                                    <div class="testimonials-card__rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="testimonials-card__content">
                                        Auto Car Repair handled my Audi's insurance claim perfectly. Their Delhi workshop made the entire process smooth and stress-free with professional service
                                    </p>
                                    <div class="testimonials-card__author">
                                        <div class="testimonials-card__image">
                                            <img src="front/images/resources/testi-1-4.jpg" alt="Neha Kapoor" loading="lazy">
                                        </div>
                                        <h3 class="testimonials-card__name">
                                                    Pratham Mehta
                                                </h3>
                                        <p class="testimonials-card__designation">Audi Owner</p>
                                    </div>
                                    <div class="testimonials-card__quote" style="background-image: url(front/images/shapes/quote-one.png);"></div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="testimonials-card wow fadeInUp" data-wow-duration='1500ms' data-wow-delay='100ms'>
                                    <div class="testimonials-card__rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="testimonials-card__content">
                                        My Land Rover's <a href="https://autocarrepair.in/ceramic-coating-delhi" class="no-style-link" rel="noopener noreferrer"
   title="ceramic coating"> ceramic coating </a> at Auto Car Repair looks stunning. Their Okhla center offers premium services at reasonable prices with excellent results
                                    </p>
                                    <div class="testimonials-card__author">
                                        <div class="testimonials-card__image">
                                            <img src="front/images/resources/testi-1-5.jpg" alt="Arjun Malhotra" loading="lazy">
                                        </div>
                                        <h3 class="testimonials-card__name">
                                                    Santosh Singh
                                                </h3>
                                        <p class="testimonials-card__designation">Land Rover Owner</p>
                                    </div>
                                    <div class="testimonials-card__quote" style="background-image: url(front/images/shapes/quote-one.png);"></div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="testimonials-card wow fadeInUp" data-wow-duration='1500ms' data-wow-delay='200ms'>
                                    <div class="testimonials-card__rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="testimonials-card__content">
                                        Auto Car Repair's maintenance package for my Toyota was comprehensive and affordable. Their expert technicians in Noida delivered perfect service as promised
                                    </p>
                                    <div class="testimonials-card__author">
                                        <div class="testimonials-card__image">
                                            <img src="front/images/resources/testi-1-6.jpg" alt="Sanjay Gupta" loading="lazy">
                                        </div>
                                        <h3 class="testimonials-card__name">
                                                    Piyush
                                                </h3>
                                        <p class="testimonials-card__designation">Toyota Owner</p>
                                    </div>
                                    <div class="testimonials-card__quote" style="background-image: url(front/images/shapes/quote-one.png);"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 d-flex align-items-center">
                    <div class="testimonials-one__content">
                        <div class="sec-title text-left">
                            <h6 class="sec-title__tagline bw-split-in-right">Testimonials<span class="sec-title__tagline__border"></span></h6>
                            <h2 class="sec-title__title bw-split-in-left">Delhi NCR <span>drivers trust</span> us</h2>
                        </div>
                        <div class="testimonials-one__carousel-nav">
                            <a href="#" class="testimonials-one__prev" aria-label="Previous testimonial"><i class="fi fi-rr-arrow-up-right"></i></a>
                            <a href="#" class="testimonials-one__next" aria-label="Next testimonial"><i class="fi fi-rr-arrow-up-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.testimonials-one -->
    <!-- /.client-carousel -->
    <div class="client-carousel">
        <div class="container">
            <div class="client-carousel__one karoons-owl__carousel owl-theme owl-carousel" data-owl-options='{
                "items": 5,
                "margin": 65,
                "smartSpeed": 700,
                "loop":true,
                "autoplay": 6000,
                "nav":false,
                "dots":false,
                "navText": ["<span class=\"fa fa-angle-left\"></span>","<span class=\"fa fa-angle-right\"></span>"],
                "responsive":{
                    "0":{
                        "items":1,
                        "margin": 0
                    },
                    "360":{
                        "items":3,
                        "margin": 10
                    },
                    "575":{
                        "items":3,
                        "margin": 30
                    },
                    "768":{
                        "items":4,
                        "margin": 40
                    },
                    "992":{
                        "items": 5,
                        "margin": 40
                    },
                    "1200":{
                        "items": 6,
                        "margin": 80
                    }
                }
            }'>
                @if($brand_logo_slider->count())
                    @foreach($brand_logo_slider as $record)
                        <div class="client-carousel__one__item">
                            <img src="{{ asset('uploads/brandlogoslider/'.$record->image) }}" alt="{{$record->image_title}}" title="{{$record->image_title}}" loading="lazy">
                        </div>
                    @endforeach
                @endif
            </div>
            <!-- /.thm-owl__slider -->
        </div>
        <!-- /.container -->
    </div>
    <!-- /.blog-three -->
    <!-- partner logo  slider end -->
    @endsection