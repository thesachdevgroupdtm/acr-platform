@extends('front.layout.main')
@section('content')
<section class="page-header">
    <div class="page-header__bg"></div>
    <div class="container">
        <h1 class="page-header__title bw-split-in-right">Detailing</h1>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{url('/')}}"><i class="flaticon-home"></i>Home</a></li>
            <li>Our Service</li>
            <li><span>Detailing</span></li>
        </ul>
    </div>
</section>
    <!-- Carousel Start -->
    <!-- <div class="container-fluid p-0 mb-5">
        <div id="header-carousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                @php
                    $carouselItems = [
                        [
                            'image' => asset('resources/views/front/detailing/img/carousel-bg-1.jpg'),
                            'subtitle' => '// Auto Car Repair //',
                            'title' => 'Expert Luxury Car Interior Cleaning Service Center',
                            'button_text' => 'Book Now'
                        ],
                        [
                            'image' => asset('resources/views/front/detailing/img/carousel-bg-2.jpg'),
                            'subtitle' => '// Auto Car Repair //',
                            'title' => 'Expert Luxury Car Detailing Service Center',
                            'button_text' => 'Book Now'
                        ],
                        [
                            'image' => asset('resources/views/front/detailing/img/carousel-bg-3.jpg'),
                            'subtitle' => '// Auto Car Repair //',
                            'title' => 'Expert Luxury Car Exterior Enhancement Service Center',
                            'button_text' => 'Book Now'
                        ]
                    ];
                @endphp

                @foreach($carouselItems as $index => $item)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <img class="w-100" src="{{ $item['image'] }}" alt="Carousel Image {{ $index + 1 }}">
                        <div class="carousel-caption d-flex align-items-center">
                            <div class="container">
                                <div class="row align-items-center justify-content-center justify-content-lg-start">
                                    <div class="col-10 col-lg-7 text-center text-lg-start">
                                        <h6 class="text-white text-uppercase mb-3 animated slideInDown">{{ $item['subtitle'] }}</h6>
                                        <h1 class="display-3 text-white mb-4 pb-3 animated slideInDown" >
                                            {{ $item['title'] }}
                                        </h1>
                                        <button onclick="openForm()" class="karoons-btn">
                                            {{ $item['button_text'] }}<i class="fi fi-rr-arrow-up-right ms-3"></i>
                                        </button> 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div> -->
    <!-- Carousel End -->
    
<!-- Feature End -->
  <!-- About Start -->
  <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 pt-4" >
                    <div class="position-relative h-100 wow fadeIn" data-wow-delay="0.1s">
                        <img class="position-absolute img-fluid w-100 h-100" src="{{ asset('resources/views/front/detailing/img/About.webp') }}" alt="About Auto Car Repair">
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="sec-title__tagline bw-split-in-right">
                    About Us<span class="sec-title__tagline__border"></span>
                    </h6>
                    <h3 class="sec-title__title bw-split-in-left">Auto Car Repair<span> – Where Luxury Meets Perfection</span></h3>
                    <p class="mb-4">Give your vehicle a new look with our comprehensive car detailing services. From precise repairs to meticulous detailing, we ensure your car receives top-notch care for improved aesthetics and optimal performance.</p>
                    
                    <div class="row g-4 mb-3 pb-3">
                        @php
                            $features = [
                                [
                                    'number' => '01',
                                    'title' => 'Professional & Expert',
                                    'description' => 'Expert care for your vehicle, ensuring reliability and top-tier performance.',
                                    'delay' => '0.1s'
                                ],
                                [
                                    'number' => '02',
                                    'title' => 'Quality Servicing Center',
                                    'description' => 'Highly skilled technicians having the expertise to diagnose and repair car issues.',
                                    'delay' => '0.3s'
                                ],
                                [
                                    'number' => '03',
                                    'title' => 'State-of-the-art Workshops',
                                    'description' => 'Equipped with the latest technology and equipment to deliver the best solutions.',
                                    'delay' => '0.5s'
                                ]
                            ];
                        @endphp

                        @foreach($features as $feature)
                            <div class="col-12 wow fadeIn" data-wow-delay="{{ $feature['delay'] }}">
                                <div class="d-flex">
                                    <div class="bg-light d-flex flex-shrink-0 align-items-center justify-content-center mt-1" >
                                        <span class="fw-bold text-secondary">{{ $feature['number'] }}</span>
                                    </div>
                                    <div class="ps-3">
                                        <h6>{{ $feature['title'] }}</h6>
                                        <span>{{ $feature['description'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <button onclick="openForm()"  class="karoons-btn">Read More<i class="fi fi-rr-arrow-up-right ms-3"></i></button>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->
<!-- Services Start -->
<div class="container-xxl service py-5">
    <div class="container">
        <div class="sec-title text-center">
            <h6 class="sec-title__tagline bw-split-in-right">
            Our Services<span class="sec-title__tagline__border"></span>
            </h6>
            
            <h3 class="sec-title__title bw-split-in-left">Explore Our<span> Services</span> </h3>
        </div>
        
        <div class="row g-4 wow fadeInUp" data-wow-delay="0.3s">
            <div class="col-lg-4">
                <div class="service-sidebar">
                    <div class="service-sidebar__single">
                        <ul class="list-unstyled service-sidebar__nav">
                            @php
                                $services = [
                                    [
                                        'id' => 'tab-pane-1',
                                        'icon' => 'fa-air-freshener',
                                        'title' => 'Ceramic Coating',
                                        'active' => true
                                    ],
                                    [
                                        'id' => 'tab-pane-2',
                                        'icon' => 'fa-car-on',
                                        'title' => 'Paint Protection Film',
                                        'active' => false
                                    ],
                                    [
                                        'id' => 'tab-pane-8',
                                        'icon' => 'fa-air-freshener',
                                        'title' => 'Teflon Coating',
                                        'active' => false
                                    ],
                                    [
                                        'id' => 'tab-pane-9',
                                        'icon' => 'fa-air-freshener',
                                        'title' => 'Rubbing Polish',
                                        'active' => false
                                    ],
                                    [
                                        'id' => 'tab-pane-12',
                                        'icon' => 'fa-car',
                                        'title' => 'Car Washing',
                                        'active' => false
                                    ],
                                    [
                                        'id' => 'tab-pane-13',
                                        'icon' => 'fa-car',
                                        'title' => 'Car Denting Painting',
                                        'active' => false
                                    ]
                                ];
                            @endphp

                            @foreach($services as $service)
                                <li>
                                    <a href="#" 
                                    class="{{ $service['active'] ? 'active' : '' }}"
                                    data-bs-toggle="pill" 
                                    data-bs-target="#{{ $service['id'] }}">
                                        <i class="fa {{ $service['icon'] }} me-2"></i>
                                        {{ $service['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="tab-content w-100">
                    @php
                        $serviceDetails = [
                            'tab-pane-1' => [
                                'image' => asset('resources/views/front/detailing/img/7xm.xyz258974-1920w.webp'),
                                'title' => 'Ceramic Coating',
                                'description' => 'Ceramic coating service is a process of applying a liquid polymer coating to a vehicle\'s exterior surfaces, enhancing its appearance, providing UV resistance, repelling contaminants, and ensuring long-lasting protection.',
                                'benefits' => [
                                    'Enhanced Aesthetics',
                                    'Protection from Harmful UV Rays',
                                    'Easy Maintenance'
                                ],
                                'link' => 'https://autocarrepair.in/ceramic-coating-service'
                            ],
                            'tab-pane-2' => [
                                'image' => asset('resources/views/front/detailing/img/ppf.webp'),
                                'title' => 'Paint Protection Film',
                                'description' => 'Paint Protection Film (PPF) is a clear film applied to vehicle exteriors, preserving paint\'s original appearance, enhancing vehicle longevity and resale value, and acting as a protective shield against weather elements.',
                                'benefits' => [
                                    'Paint Preservation',
                                    'Enhanced Resale Value',
                                    'Long-Term Protection'
                                ],
                                'link' => 'https://autocarrepair.in/ppf-coating-service'
                            ],
                            'tab-pane-8' => [
                                'image' => asset('resources/views/front/detailing/img/teflon.webp'),
                                'title' => 'Teflon Coating',
                                'description' => 'The Car Teflon coating service is a nonstick polymer application that provides long-lasting protection against environmental factors, enhancing the car\'s aesthetic appeal and minimizing the impact of road debris and bird droppings.',
                                'benefits' => [
                                    'Water and Contaminant Repellency',
                                    'Sleek Aesthetic Finish',
                                    'Enhanced Durability'
                                ],
                                'link' => 'https://autocarrepair.in/teflon-coating-service'
                            ],
                            'tab-pane-9' => [
                                'image' => asset('resources/views/front/detailing/img/rubbing.webp'),
                                'title' => 'Rubbing Polish',
                                'description' => 'Rubbing and polishing are automotive detailing techniques that improve a vehicle\'s exterior by removing surface imperfections like scratches and oxidation, while refining the finish, restoring shine, and creating a smooth surface.',
                                'benefits' => [
                                    'Surface Restoration',
                                    'Enhanced Gloss and Shine',
                                    'Preservation of Paintwork'
                                ],
                                'link' => 'https://autocarrepair.in/rubbing-polish'
                            ],
                            'tab-pane-12' => [
                                'image' => asset('resources/views/front/detailing/img/pexels-tima-miroshnichenko-68731.webp'),
                                'title' => 'Car Washing',
                                'description' => 'Car washing services are not just about aesthetics; they also contribute to the well-being of your vehicle by removing dirt, road salt, and contaminants, preventing damage and enhancing safety through clear visibility through clean windows.',
                                'benefits' => [
                                    'Aesthetic Appeal',
                                    'Increased Safety',
                                    'Long-Term Maintenance'
                                ],
                                'link' => 'https://autocarrepair.in/car-washing-service'
                            ],
                            'tab-pane-13' => [
                                'image' => asset('resources/views/front/detailing/img/Car-Painting_Automagic.webp'),
                                'title' => 'Car Denting Painting',
                                'description' => 'Car denting and painting services use specialized tools to restore and protect your car\'s exterior, ensuring its original shape and a flawless appearance. Paint is applied to blend with the existing finish.',
                                'benefits' => [
                                    'Aesthetic Restoration',
                                    'Prevention of Further Damage',
                                    'Higher Resale Value'
                                ],
                                'link' => 'https://autocarrepair.in/dent-paint'
                            ]
                        ];
                    @endphp

                    @foreach($serviceDetails as $id => $detail)
                        <div class="tab-pane fade {{ $id === 'tab-pane-1' ? 'show active' : '' }}" id="{{ $id }}">
                            <div class="row g-4">
                                <div class="col-md-12" style="min-height: 350px;">
                                    <div class="position-relative h-100">
                                        <img class="position-absolute img-fluid w-100 h-100" src="{{ $detail['image'] }}" alt="{{ $detail['title'] }}">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <h3 class="mb-3">{{ $detail['title'] }}</h3>
                                    <p class="mb-4">{{ $detail['description'] }}</p>
                                    @foreach($detail['benefits'] as $benefit)
                                        <p><i class="fa fa-check text-success me-3"></i>{{ $benefit }}</p>
                                    @endforeach
                                    <a href="{{ $detail['link'] }}" class="karoons-btn">Read More<i class="fi fi-rr-arrow-up-right ms-3"></i></a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Services End -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Get all service tab links
    const serviceLinks = document.querySelectorAll('.service-sidebar__nav li a');
    
    // Add click event listener to each link
    serviceLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default anchor behavior
            
            // Remove active class from all links
            serviceLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Get the target tab pane ID
            const targetId = this.getAttribute('data-bs-target');
            
            // Hide all tab panes
            const tabPanes = document.querySelectorAll('.tab-content .tab-pane');
            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Show the target tab pane
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
    });
</script>

<section class="work-process-one">
    <div class="container">
        <div class="sec-title text-center">
            <h6 class="sec-title__tagline bw-split-in-right">
                Repair Process<span class="sec-title__tagline__border"></span>
            </h6>
            <h3 class="sec-title__title bw-split-in-left">
                Auto <span>Car</span> Repair <br> Process In<span> Four Basic</span> Steps
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 wow fadeInUp animated" data-wow-delay="400ms">
                <div class="work-process-one__border"></div>
            </div>
        </div>

        <!-- Swiper -->
        <div class="swiper work-process-slider">
            <div class="swiper-wrapper">
                @php
                    $processes = [
                        [
                            'delay' => '00ms',
                            'title' => 'Assessment & estimates',
                            'text' => 'Our experts inspect your car in detail and provide fair, accurate service estimates on the spot.'
                        ],
                        [
                            'delay' => '100ms',
                            'title' => 'Repair Work',
                            'text' => 'From dents to scratches, our technicians handle all cosmetic repairs with professional precision.'
                        ],
                        [
                            'delay' => '200ms',
                            'title' => 'Cleaning and Detailing',
                            'text' => 'We restore your car\'s original shine with deep cleaning, polishing, and protective coatings.'
                        ],
                        [
                            'delay' => '300ms',
                            'title' => 'Pickup',
                            'text' => 'Convenient car pickup and drop service ensures a hassle-free detailing experience for you.'
                        ]
                    ];
                @endphp

                @foreach($processes as $index => $process)
                    <div class="swiper-slide wow fadeInUp" data-wow-delay="{{ $process['delay'] }}">
                        <div class="work-process-one__item text-center">
                            <div class="work-process-one__item__number">
                                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                            </div>
                            <h4 class="work-process-one__item__title">
                                {{ ucfirst($process['title']) }}
                            </h4>
                            <p class="work-process-one__item__text">
                                {{ $process['text'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Custom Progress Bar -->
            <div class="swiper-progress-bar">
                <div class="swiper-progress"></div>
            </div>
        </div>
    </div>
</section>

<!-- Swiper CSS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">

<!-- Custom CSS -->
<style>
    .swiper-progress-bar {
        height: 5px;
        background-color: #e0e0e0;
        position: relative;
        margin-top: 20px;
        border-radius: 4px;
        overflow: hidden;
    }

    .swiper-progress {
        height: 100%;
        width: 0%;
        background-color: red;
        transition: width 0.3s ease-in-out;
    }
</style>

<!-- Swiper JS -->
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

<!-- Swiper Init with Progress Fix -->
<script>
    var swiper = new Swiper('.work-process-slider', {
        slidesPerView: 1,
        spaceBetween: 10,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
            1024: {
                slidesPerView: 4,
                spaceBetween: 40,
            },
        },
        on: {
            init: function () {
                updateProgress(this);
            },
            slideChange: function () {
                updateProgress(this);
            },
            resize: function () {
                updateProgress(this);
            }
        }
    });

    function updateProgress(swiperInstance) {
        var totalPositions = swiperInstance.snapGrid.length - 1;
        var progress = (swiperInstance.activeIndex / totalPositions) * 100;
        document.querySelector('.swiper-progress').style.width = Math.min(progress, 100) + '%';
    }
</script>



    <!-- Stats End -->
        <!-- Work Process End -->
        <section class="car-showcase">
            <div class="car-showcase__inner">
                <div class="car-showcase__image wow fadeInUp">
                    <img src="front/images/resources/car-showcase-1.png" alt="car detailing">
                </div>
            </div>
        </section><!-- /.car-showcase -->

          
    <!-- Booking Start -->
    <section class="contact-one" style="background-image: url(front/images/shapes/contact-bg-1.png);">
            <div class="contact-one__bg wow slideInRight" data-wow-delay="100ms" style="background-image: url(front/images/backgrounds/contact-bg-1.jpg);"></div>
            <div class="container">
                <div class="col-lg-8">
                   <form class="contact-one__form contact-form-validated form-one wow fadeInUp ajax-form" m method="POST" 
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
                </div><!-- /.col-lg-8 -->
            </div><!-- /.container -->
        </section><!-- /.contact-one -->

 
    <!-- Testimonials start -->

    <?php
$testimonials = [
    [
        'rating' => 5,
        'content' => 'Exceptional service and value for money! The owner\'s expertise and kindness shine through in every interaction. Grateful for the consistently outstanding service. Keep up the great work! Thank you',
        'image' => 'front/images/resources/testi-1-1.jpg',
        'name' => 'Atul Tiwari',
        'designation' => 'Audi Q8'
    ],
    [
        'rating' => 5,
        'content' => 'ACR Moti Nagar excelled in hospitality, thanks to Mr. Sanjeev Ji\'s management. Though costly, membership\'s benefits justify expense. Impeccable service from inside out guarantees true value for money',
        'image' => 'front/images/resources/testi-1-2.jpg',
        'name' => 'Harsh Sharma',
        'designation' => 'G-Wagon'
    ],
    [
        'rating' => 5,
        'content' => 'Recently had paint protection applied to my car. Prompt service and excellent results! Highly recommend for anyone looking to protect their vehicle\'s paintwork. Top-notch job',
        'image' => 'front/images/resources/testi-1-3.jpg',
        'name' => 'Vikas Pandey',
        'designation' => 'Hyundai Aura'
    ]
];
?>

<section class="testimonials-one">
    <div class="testimonials-one__shape" style="background-image: url(front/images/resources/testimonial-1-shape-1.png);"></div>
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="karoons-stretch-element-inside-column">
                    <div class="testimonials-one__carousel karoons-owl__carousel owl-carousel karoons-owl__carousel--custom-nav"
                        data-owl-nav-prev=".testimonials-one__prev"
                        data-owl-nav-next=".testimonials-one__next"
                        data-owl-options='{
                            "items": 1,
                            "margin": 30,
                            "loop": true,
                            "smartSpeed": 700,
                            "rtl": true,
                            "nav": false,
                            "dots": false,
                            "autoplay": false,
                            "responsive": {
                                "0": {"items": 1.05},
                                "768": {"items": 1.4},
                                "900": {"items": 1.2},
                                "1300": {"items": 1.05},
                                "1600": {"items": 1.73}
                            }
                        }'>

                        <?php foreach ($testimonials as $index => $testimonial): ?>
                            <div class="item">
                                <div class="testimonials-card wow fadeInUp" data-wow-duration='1500ms' data-wow-delay='<?= $index * 100 ?>ms'>
                                    <div class="testimonials-card__rating">
                                        <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="testimonials-card__content">
                                        <?= $testimonial['content'] ?>
                                    </div>
                                    <div class="testimonials-card__author">
                                        <div class="testimonials-card__image">
                                            <img src="<?= $testimonial['image'] ?>" alt="<?= $testimonial['name'] ?>">
                                        </div>
                                        <h3 class="testimonials-card__name"><?= $testimonial['name'] ?></h3>
                                        <p class="testimonials-card__designation"><?= $testimonial['designation'] ?></p>
                                    </div>
                                    <div class="testimonials-card__quote" style="background-image: url(front/images/shapes/quote-one.png);"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-flex align-items-center">
                <div class="testimonials-one__content">
                    <div class="sec-title text-left">
                        <h6 class="sec-title__tagline bw-split-in-right">Testimonials<span class="sec-title__tagline__border"></span></h6>
                        <h3 class="sec-title__title bw-split-in-left">People <span>talk about</span> us</h3>
                    </div>
                    <div class="testimonials-one__carousel-nav">
                        
                        <a href="#" class="testimonials-one__prev"><i class="fi fi-rr-arrow-up-right"></i></a>
                        <a href="#" class="testimonials-one__next"><i class="fi fi-rr-arrow-up-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection