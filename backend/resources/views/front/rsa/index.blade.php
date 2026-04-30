@extends('front.layout.main')
@section('content')

<section class="page-header page-header--details">
    <div class="page-header__bg"></div>
    <!-- /.page-header__bg -->
    <div class="container">
        <h1 class="page-header__title bw-split-in-right">Roadside Assistance</h1>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{ url('/') }}"><i class="flaticon-home"></i>Home</a></li>
            <li><span>Roadside Assistance</span></li>
        </ul><!-- /.thm-breadcrumb list-unstyled -->
    </div><!-- /.container -->
</section><!-- /.page-header -->

<section class="about-one about-one--about-page">
    <div class="container">
        <div class="row">
            <div class="col-lg-5">
                <div class="about-one--about-page__image wow fadeInLeft" data-wow-delay="100ms">
                    <img src="resources/views/front/rsa/assets/images/rsa.jpg" alt="Modern Workshop">
                </div><!-- /.about-one__image -->
            </div><!-- /.col-lg-5-->
            <div class="col-lg-7 wow fadeInRight" data-wow-delay="300ms">
                <div class="about-one__content">
                    <div class="sec-title text-left">
                        <h6 class="sec-title__tagline bw-split-in-right">Roadside Assistance<span class="sec-title__tagline__border"></span></h6><!-- /.sec-title__tagline -->
                        <h3 class="sec-title__title bw-split-in-left">24/7 ROADSIDE<br> <span>ASSISTANCE</span> FOR CARS</h3><!-- /.sec-title__title -->
                    </div><!-- /.sec-title -->
                    <h5 class="about-one__content__heading">Welcome to Auto Car Repair!</h5>
                    <p class="about-one__content__text">
                        Auto Car Repair offers reliable roadside assistance, ensuring you're never stranded. Our certified technicians provide jump starts, tire changes, lockout services, etc., to get you back on the road swiftly and safely, wherever you may be.
                    </p>
                    <ul class="about-one__content__list">
                        <li><i class="fi fi-rr-check-mark"></i>MODERN WORKSHOP</li>
                        <li><i class="fi fi-rr-check-mark"></i>QUALITY ASSURANCE</li>
                        <li><i class="fi fi-rr-check-mark"></i>CERTIFIED EXPERTS</li>
                    </ul>
                    <div class="about-one__content__info-wrapper">
                        <a href="resources/views/front/rsa/assets/rsa.pdf" class="karoons-btn"><span><i class="fi fi-rr-arrow-up-right"></i>Download Brochure</span></a>
                        <div class="about-one__content__info">
                            <div class="about-one__content__info__icon"><i class="fi fi-rr-phone-call"></i></div>
                            <div class="about-one__content__info__content">
                                <p class="about-one__content__info__title">call for assistance</p>
                                <h5 class="about-one__content__info__number"><a href="tel:9870400861">+91-9870400861</a></h5>
                            </div>
                        </div>
                    </div>
                </div><!-- /.why-choose-one__content -->
            </div><!-- /.col-lg-6 -->
        </div><!-- /.row -->
    </div><!-- /.container -->
</section><!-- /.about-one -->

<!-- ***** Our Classes Start ***** -->

<div class="container-xxl service py-5">
    <div class="container">
        <div class="sec-title text-center">
            <h6 class="sec-title__tagline bw-split-in-right">
                Key Benefits<span class="sec-title__tagline__border"></span>
            </h6>

            <h3 class="sec-title__title bw-split-in-left">Our 24x7 Roadside Assistance<span> Benefits</span></h3>
            <p class="mt-3">Our 24x7 Roadside Assistance is Designed to Keep You Protected on the Road With Our Experts Available Anywhere and Anytime</p>
        </div>

        <div class="row g-4 wow fadeInUp" data-wow-delay="0.3s">
            <div class="col-lg-4">
                <div class="service-sidebar">
                    <div class="service-sidebar__single">
                        <ul class="list-unstyled service-sidebar__nav">
                            @php
                            $services = [
                            [
                            'id' => 'tabs-1',
                            'icon' => 'fa-phone',
                            'title' => '24/7 Emergency Assistance',
                            'active' => true
                            ],
                            [
                            'id' => 'tabs-2',
                            'icon' => 'fa-wrench',
                            'title' => 'Flat Tire Assistance',
                            'active' => false
                            ],
                            [
                            'id' => 'tabs-3',
                            'icon' => 'fa-battery-full',
                            'title' => 'Battery Jump Start',
                            'active' => false
                            ],
                            [
                            'id' => 'tabs-4',
                            'icon' => 'fa-lock',
                            'title' => 'Lockout Service',
                            'active' => false
                            ],
                            [
                            'id' => 'tabs-5',
                            'icon' => 'fa-truck',
                            'title' => 'Towing Services',
                            'active' => false
                            ],
                            [
                            'id' => 'tabs-6',
                            'icon' => 'fa-gas-pump',
                            'title' => 'Fuel Delivery',
                            'active' => false
                            ],
                            [
                            'id' => 'tabs-7',
                            'icon' => 'fa-hotel',
                            'title' => 'Emergency Cab & Accommodation',
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
                    <div class="tab-pane fade show active" id="tabs-1">
                        <div class="row g-4">
                            <div class="col-md-12" style="min-height: 350px;">
                                <div class="position-relative h-100">
                                    <img class="position-absolute img-fluid w-100 h-100" src="resources/views/front/rsa/assets/images/1 (7).webp" alt="24/7 Emergency Assistance">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <h3 class="mb-3">24/7 Emergency Assistance</h3>
                                <p class="mb-4">Whether it's a roadside breakdown, lockout, or other emergencies, our dedicated team is ready to provide swift and reliable assistance, day or night. Your safety and peace of mind are our top priorities.</p>
                                <a href="tel:9870400861" class="karoons-btn">Call Now<i class="fi fi-rr-arrow-up-right ms-3"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabs-2">
                        <div class="row g-4">
                            <div class="col-md-12" style="min-height: 350px;">
                                <div class="position-relative h-100">
                                    <img class="position-absolute img-fluid w-100 h-100" src="resources/views/front/rsa/assets/images/1 (5).webp" alt="Flat Tire Assistance">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <h3 class="mb-3">Flat Tire Assistance</h3>
                                <p class="mb-4">Experience a flat tire? Our prompt and efficient flat tire assistance service is here to help. Call us anytime, anywhere, and our skilled team will quickly come to your aid, ensuring you're back on the road with minimal disruption.</p>
                                <a href="tel:9870400861" class="karoons-btn">Call Now<i class="fi fi-rr-arrow-up-right ms-3"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabs-3">
                        <div class="row g-4">
                            <div class="col-md-12" style="min-height: 350px;">
                                <div class="position-relative h-100">
                                    <img class="position-absolute img-fluid w-100 h-100" src="resources/views/front/rsa/assets/images/1 (6).webp" alt="Battery Jump Start">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <h3 class="mb-3">Battery Jump Start</h3>
                                <p class="mb-4">Need a jump start? Our expert team is ready to provide swift battery jump start assistance. Call us anytime, anywhere, and we'll have you back on the road in no time. Depend on us for reliable service when you need it most.</p>
                                <a href="tel:9870400861" class="karoons-btn">Call Now<i class="fi fi-rr-arrow-up-right ms-3"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabs-4">
                        <div class="row g-4">
                            <div class="col-md-12" style="min-height: 350px;">
                                <div class="position-relative h-100">
                                    <img class="position-absolute img-fluid w-100 h-100" src="resources/views/front/rsa/assets/images/1 (4).webp" alt="Lockout Service">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <h3 class="mb-3">Lockout Service</h3>
                                <p class="mb-4">Locked out of your car? Our professional lockout service is just a call away. Our skilled technicians will swiftly and safely assist you in gaining access to your vehicle, providing reliable help when you need it most. Your convenience and security matter to us.</p>
                                <a href="tel:9870400861" class="karoons-btn">Call Now<i class="fi fi-rr-arrow-up-right ms-3"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabs-5">
                        <div class="row g-4">
                            <div class="col-md-12" style="min-height: 350px;">
                                <div class="position-relative h-100">
                                    <img class="position-absolute img-fluid w-100 h-100" src="resources/views/front/rsa/assets/images/9.webp" alt="Towing Services">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <h3 class="mb-3">Towing Services</h3>
                                <p class="mb-4">When you need towing, trust our reliable service to get your vehicle where it needs to go. Our skilled team provides efficient and safe towing assistance, ensuring your peace of mind during unexpected situations.</p>
                                <a href="tel:9870400861" class="karoons-btn">Call Now<i class="fi fi-rr-arrow-up-right ms-3"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabs-6">
                        <div class="row g-4">
                            <div class="col-md-12" style="min-height: 350px;">
                                <div class="position-relative h-100">
                                    <img class="position-absolute img-fluid w-100 h-100" src="resources/views/front/rsa/assets/images/1 (2).webp" alt="Fuel Delivery">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <h3 class="mb-3">Fuel Delivery</h3>
                                <p class="mb-4">Out of fuel? Don't worry! Our prompt fuel delivery service ensures you're back on the road without delay. Just give us a call, and our team will deliver the fuel you need, wherever you are.</p>
                                <a href="tel:9870400861" class="karoons-btn">Call Now<i class="fi fi-rr-arrow-up-right ms-3"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabs-7">
                        <div class="row g-4">
                            <div class="col-md-12" style="min-height: 350px;">
                                <div class="position-relative h-100">
                                    <img class="position-absolute img-fluid w-100 h-100" src="resources/views/front/rsa/assets/images/1 (1).webp" alt="Emergency Cab & Accommodation">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <h3 class="mb-3">Emergency Cab & Accommodation</h3>
                                <p class="mb-4">Facing an unexpected emergency on the road? Our service goes beyond repairs. We provide emergency cab and accommodation assistance, ensuring your safety and comfort during unforeseen situations. Count on us for comprehensive support when you need it most.</p>
                                <a href="tel:9870400861" class="karoons-btn">Call Now<i class="fi fi-rr-arrow-up-right ms-3"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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


<!-- ***** Call to Action End ***** -->

<!-- Work Process Start -->
<section class="work-process-one">
    <div class="container">
        <div class="sec-title text-center">

            <h6 class="sec-title__tagline bw-split-in-right">Why Choose Us<span class="sec-title__tagline__border"></span></h6><!-- /.sec-title__tagline -->

            <div class="auto-repair-process">
                <h3 class="sec-title__title bw-split-in-left">
                    Benefits of <span>Roadside Assistance</span> Services
                </h3>


                <div class="commitment-text">
                    <p>Our commitment goes beyond the ordinary—whether you're dealing with a flat tire, battery issues, or any unexpected mishap, our team of experts is just a call away.</p>
                </div>
            </div><!-- /.sec-title__title -->
        </div><!-- /.sec-title -->
        <div class="row">
            <div class="col-lg-12 wow fadeInUp animated" data-wow-delay="400ms">
                <div class="work-process-one__border"></div>
            </div>
        </div>
        <div class="row gutter-y-30">
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="00ms">
                <div class="work-process-one__item text-center">
                    <div class="work-process-one__item__number"></div><!-- /.work-process-number -->
                    <h4 class="work-process-one__item__title">Peace of Mind</h4><!-- /.work-process-title -->
                    <p class="work-process-one__item__text">
                        Knowing that 24/7 roadside assistance is just a call away reduces stress and anxiety often linked with unexpected car troubles.
                    </p><!-- /.work-process-text -->
                </div><!-- /.work-process-item -->
            </div><!-- item -->
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="100ms">
                <div class="work-process-one__item text-center">
                    <div class="work-process-one__item__number"></div><!-- /.work-process-number -->
                    <h4 class="work-process-one__item__title">Cost Savings </h4><!-- /.work-process-title -->
                    <p class="work-process-one__item__text">Bundled roadside services (towing, lockout, etc.) are more affordable than paying for individual emergency car repairs separately.
                    </p><!-- /.work-process-text -->
                </div><!-- /.work-process-item -->
            </div><!-- item -->
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                <div class="work-process-one__item text-center">
                    <div class="work-process-one__item__number"></div><!-- /.work-process-number -->
                    <h4 class="work-process-one__item__title">Convenience</h4><!-- /.work-process-title -->
                    <p class="work-process-one__item__text">Roadside assistance offers quick help, saving you from the hassle of searching for a tow truck or repair service on your own.
                    </p><!-- /.work-process-text -->
                </div><!-- /.work-process-item -->
            </div><!-- item -->
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="00ms">
                <div class="work-process-one__item text-center">
                    <div class="work-process-one__item__number"></div><!-- /.work-process-number -->
                    <h4 class="work-process-one__item__title">Customization Options</h4><!-- /.work-process-title -->
                    <p class="work-process-one__item__text">Our roadside assistance plans include multiple coverage levels, letting you choose the right option for your budget and needs.
                    </p><!-- /.work-process-text -->
                </div><!-- /.work-process-item -->
            </div><!-- item -->
        </div>
    </div>
</section>
<!-- Work Process End -->
<section class="car-showcase">
    <div class="car-showcase__inner" style="background-image: url('front/images/shapes/contact-bg-1.png');">
        <div class="car-showcase__image wow fadeInUp">
            <img src="resources\views\front\rsa\assets\images\rsaa-Photoroom.png" alt="car service">
        </div>
    </div>
</section><!-- /.car-showcase -->
<!-- Pricing Section -->
<section class="section pt-5 pb-5" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center mb-5">
                <div class="sec-title">
                    <h6 class="sec-title__tagline bw-split-in-right">Roadside Assistance Plans<span class="sec-title__tagline__border"></span></h6>
                    <h3 class="sec-title__title bw-split-in-left">Anytime, <span>Anywhere,</span> By <span>Your Side!</span></h3>
                    <p class="mt-3">Stay Protected on the Road With Our 24x7 Roadside Assistance, Providing You with Reliable Support Whenever and Wherever You Need It</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="pricing__table" style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div class="pt__title">
                        <div class="pt__title__wrap d-flex">
                            <div class="pt__row flex-fill text-center p-3 fw-bold">Vehicle Age</div>
                            <div class="pt__row flex-fill text-center p-3 fw-bold">1 Year Premium</div>
                            <div class="pt__row flex-fill text-center p-3 fw-bold">1 Year</div>
                            <div class="pt__row flex-fill text-center p-3 fw-bold">6 Month</div>
                        </div>
                    </div>
                    <div class="pt__option">
                        <div class="pt__option__item">
                            <div class="pt__item">
                                <div class="pt__item__wrap d-flex">
                                    <div class="pt__row flex-fill text-center p-3">Between 1-7 years Vehicle</div>
                                    <div class="pt__row flex-fill text-center p-3">₹1499</div>
                                    <div class="pt__row flex-fill text-center p-3">₹999</div>
                                    <div class="pt__row flex-fill text-center p-3">₹499</div>
                                </div>
                            </div>
                        </div>
                        <div class="pt__option__item">
                            <div class="pt__item selected">
                                <div class="pt__item__wrap d-flex">
                                    <div class="pt__row flex-fill text-center p-3">Between 8-10 years Vehicle</div>
                                    <div class="pt__row flex-fill text-center p-3">₹1999</div>
                                    <div class="pt__row flex-fill text-center p-3">₹1499</div>
                                    <div class="pt__row flex-fill text-center p-3">₹799</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ***** Features Item End ***** -->
<section class="faq-one faq-one--home-two">
    <div class="faq-one--home-two__bg" style="background-image: url(front/images/shapes/faq-bg-1-home-two.png);"></div>
    <div class="container">
        <div class="row">

            <div class="col-lg-12">
                <div class="faq-one__accordion karoons-accrodion" data-grp-name="karoons-accrodion">
                    <div class="sec-title text-left">
                        <h6 class="sec-title__tagline bw-split-in-right">Faq<span class="sec-title__tagline__border"></span></h6><!-- /.sec-title__tagline -->
                        <h3 class="sec-title__title bw-split-in-left">Frequently Asked <span>Questions</span></h3><!-- /.sec-title__title -->
                    </div><!-- /.sec-title -->

                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                What is RSA?
                                <span class="accrodion-title__icon"></span><!-- /.accrodion-title__icon -->
                            </h4>
                        </div><!-- /.accordian-title -->
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>Roadside assistance is a service that provides help and support when your vehicle breaks down or encounters issues while on the road. It often includes services like towing, flat-tyre changes, jump-starts, and more.</p>
                            </div><!-- /.accordian-content -->
                        </div>
                    </div><!-- /.accordian-item -->

                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                What services are typically covered by roadside assistance?
                                <span class="accrodion-title__icon"></span><!-- /.accrodion-title__icon -->
                            </h4>
                        </div><!-- /.accordian-title -->
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>Common services include towing, flat tyre assistance, battery jump-starts, fuel delivery, lockout assistance, and minor roadside repairs.</p>
                            </div><!-- /.accordian-content -->
                        </div>
                    </div><!-- /.accordian-item -->

                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                How do I request roadside assistance?
                                <span class="accrodion-title__icon"></span><!-- /.accrodion-title__icon -->
                            </h4>
                        </div><!-- /.accordian-title -->
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>In the unlikely event that you need roadside help, you can contact us at our 24/7 hotline number <a href="tel:9870400861" style="color: #ff4500; font-weight: 600;">+91-9870400861</a>.</p>
                            </div><!-- /.accordian-content -->
                        </div>
                    </div><!-- /.accordian-item -->

                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                How quickly will help arrive after I request assistance?
                                <span class="accrodion-title__icon"></span><!-- /.accrodion-title__icon -->
                            </h4>
                        </div><!-- /.accordian-title -->
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>Response times vary, but many we strive to reach you within a certain timeframe, often within an hour of your call.</p>
                            </div><!-- /.accordian-content -->
                        </div>
                    </div><!-- /.accordian-item -->

                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                Can I transfer my roadside assistance to another vehicle?
                                <span class="accrodion-title__icon"></span><!-- /.accrodion-title__icon -->
                            </h4>
                        </div><!-- /.accordian-title -->
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>It depends on the type of plan you have. Some plans are vehicle-specific, while others may allow you to transfer coverage to another vehicle you own.</p>
                            </div><!-- /.accordian-content -->
                        </div>
                    </div><!-- /.accordian-item -->

                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                How much does roadside assistance cost?
                                <span class="accrodion-title__icon"></span><!-- /.accrodion-title__icon -->
                            </h4>
                        </div><!-- /.accordian-title -->
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>Costs vary based on vehicle type, the level of coverage, and additional services offered.</p>
                            </div><!-- /.accordian-content -->
                        </div>
                    </div><!-- /.accordian-item -->

                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                Can roadside assistance help with non-mechanical issues, such as running out of gas?
                                <span class="accrodion-title__icon"></span><!-- /.accrodion-title__icon -->
                            </h4>
                        </div><!-- /.accordian-title -->
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>Yes, some of our roadside assistance plans include services like delivering a small amount of fuel if you run out.</p>
                            </div><!-- /.accordian-content -->
                        </div>
                    </div><!-- /.accordian-item -->
                </div>
            </div><!-- /.col-lg-6 -->
        </div><!-- /.row -->
    </div><!-- /.container -->
</section><!-- /.faq-one -->

<section class="contact-one" style="background-image: url('front/images/shapes/contact-bg-1.png');">
    <div class="contact-one__bg wow slideInRight" data-wow-delay="100ms" style="background-image: url('front/images/backgrounds/contact-bg-1--team-details.jpg');"></div>
    <div class="container">
        <div class="col-lg-8">
            <!-- Updated form template with security features -->
            <form class="contact-one__form contact-form-validated form-one wow fadeInUp ajax-form"
                method="POST"
                action="{{ route('enquiry.submit') }}"
                id="compny-form"
                enctype="multipart/form-data">

                @csrf <!-- Laravel CSRF protection -->

                <div class="contact-one__form__dot-one"></div>
                <div class="contact-one__form__dot-two"></div>
                <div class="contact-one__form__dot-three"></div>
                <div class="contact-one__form__dot-four"></div>

                <div class="sec-title text-left">
                    <h6 class="sec-title__tagline bw-split-in-right">Contact Us<span class="sec-title__tagline__border"></span></h6>
                    <h3 class="sec-title__title bw-split-in-left">Have Questions? <br>Get In <span>Touch!</span></h3>
                </div>

                <div class="form-one__group">
                    <!-- ENHANCED NAME FIELD -->
                    <div class="form-one__control">
                        <input id="first-name"
                            type="text"
                            name="name"
                            required
                            placeholder="Enter Your Full Name"
                            maxlength="50"
                            pattern="[a-zA-Z\s\-\'\.]+"
                            title="Name can only contain letters, spaces, hyphens, and apostrophes"
                            autocomplete="name">
                        <!-- Validation hint -->
                        <div class="validation-hint" style="font-size: 11px; color: #666; margin-top: 2px; display: none;">
                            Only letters, spaces, and basic punctuation allowed
                        </div>
                    </div>

                    <!-- ENHANCED EMAIL FIELD -->
                    <div class="form-one__control">
                        <input id="email"
                            type="email"
                            name="email"
                            required
                            placeholder="Enter Your Email"
                            maxlength="255"
                            autocomplete="email">
                    </div>

                    <!-- ENHANCED LOCATION FIELD -->
                    <div class="form-one__control">
                        <select id="location"
                            name="location"
                            style="background-color: #F4F4F4; color: #838383; height: 50px; width: 100%; padding: 0 30px; border: none;"
                            required>
                            <option value="" disabled selected>Select Location</option>
                            <option value="ACR Motinagar">Motinagar</option>
                            <option value="ACR Gurgaon">Gurgaon</option>
                            <option value="ACR Noida">Noida</option>
                            <option value="ACR Okhla">Okhla</option>
                        </select>
                    </div>

                    <!-- ENHANCED PHONE FIELD -->
                    <div class="form-one__control">
                        <input type="tel"
                            name="phone"
                            id="phone_number"
                            required
                            maxlength="10"
                            placeholder="Enter Your Phone Number"
                            pattern="[789]\d{9}"
                            title="Please enter a 10-digit phone number starting with 7, 8, or 9"
                            autocomplete="tel">
                        <!-- Validation hint -->
                        <div class="validation-hint" style="font-size: 11px; color: #666; margin-top: 2px; display: none;">
                            10 digits starting with 7, 8, or 9
                        </div>
                    </div>

                    <!-- ENHANCED MESSAGE FIELD -->
                    <div class="form-one__control form-one__control--full">
                        <label for="message">Write Message (Optional)</label>
                        <textarea id="message"
                            name="message"
                            placeholder="Tell us about your car service needs..."
                            maxlength="500"
                            rows="4"></textarea>
                        <div class="char-count" style="font-size: 11px; color: #666; text-align: right; margin-top: 2px;">
                            <span id="message-count">0</span>/500 characters
                        </div>
                    </div>

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

                    <!-- ENHANCED CAPTCHA -->
                    <div class="form-one__control">
                        <div class="captcha-container" style="display: flex; align-items: center; gap: 12px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                            <label class="captcha-question" style="font-weight: bold; color: #495057; min-width: 100px; font-size: 14px;">
                                Loading captcha...
                            </label>
                            <input type="text"
                                class="captcha-answer"
                                name="captcha"
                                placeholder="Enter answer"
                                required
                                style="flex: 1; height: 40px; padding: 0 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
                            <input type="hidden" class="correct-answer" name="correct_answer">
                        </div>
                    </div>

                    <!-- ENHANCED AGREE CHECKBOX -->
                    <div class="form-one__control form-one__control--full">
                        <div class="agree-line" style="display: flex; align-items: flex-start; gap: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            <input type="checkbox"
                                name="agree"
                                id="agree-checkbox"
                                required
                                value="1"
                                style="margin-top: 3px; transform: scale(1.2);">
                            <label for="agree-checkbox" style="font-size: 13px; line-height: 1.4; color: #495057;">
                                I agree to receive calls, e-mail, WhatsApp messages, and SMS from ACR for service updates and promotional offers.
                            </label>
                        </div>
                    </div>

                    <!-- ENHANCED SUBMIT BUTTON -->
                    <div class="form-one__control form-one__control--full">
                        <button type="submit" class="karoons-btn">
                            <span><i class="fi fi-rr-arrow-up-right"></i>Submit</span>
                        </button>
                    </div>
                </div>

                <!-- SUCCESS MESSAGE (hidden by default) -->
                <div class="success-message" style="display: none; padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 6px; margin-top: 15px;">
                    <i class="fas fa-check-circle"></i> Thank you! Your enquiry has been submitted successfully. We'll contact you soon.
                </div>
            </form>

        </div>
    </div>
</section>

@endsection