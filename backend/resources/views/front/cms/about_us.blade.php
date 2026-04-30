@extends('front.layout.main') 
@section('content')
<section class="page-header">
    <div class="page-header__bg"></div>
    <!-- /.page-header__bg -->
    <div class="container">
        <h1 class="page-header__title bw-split-in-right">About us</h1>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{url('/')}}"><i class="flaticon-home"></i>Home</a></li>
            <li><span>About</span></li>
        </ul><!-- /.thm-breadcrumb list-unstyled -->
    </div><!-- /.container -->
</section><!-- /.page-header -->
<style>
@media only screen and (max-width: 768px) {
    .about-one--about-page__image__two {
        display: none !important;
    }
}
</style>
<section class="about-one about-one--about-page">
    <div class="container">
        <div class="row align-items-start">
            <!-- Left Image -->
            <div class="col-lg-6">
                <div class="about-one--about-page__image wow fadeInLeft" data-wow-delay="100ms">
                    <img src="front/images/resources/about-1-3.jpg" alt="Workshop - Auto Car Repair">
                    <div class="about-one--about-page__image__two wow fadeInUp" data-wow-delay="300ms">
                        <img src="front/images/resources/about-1-4.jpg" alt="Car Repair Station">
                    </div>
                </div>
            </div>
            <!-- Right Text Content -->
            <div class="col-lg-6 wow fadeInRight" data-wow-delay="300ms">
                <div class="about-one__content">
                    <div class="sec-title text-left">
                        <h6 class="sec-title__tagline bw-split-in-right">About Us<span class="sec-title__tagline__border"></span></h6>
                        <h3 class="sec-title__title bw-split-in-left">AUTO <span style="color:#005EFF;">CAR</span> REPAIR</h3>
                    </div>
                    <h5 class="about-one__content__heading">Delhi NCR's Trusted Multi-Brand Car Repair Network</h5>
                    <p class="about-one__content__text">
                        <strong><a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a></strong> (Powered by Autogine Services) stands as a premier <strong>multi-brand car service chain in Delhi NCR</strong>. We are proud to be <strong>authorized partners of Galaxy Toyota and Hans Hyundai</strong>, while servicing all brands like BMW, Audi, Mercedes-Benz, Maruti, Tata, Hyundai, and more.
                    </p>
                    <p class="about-one__content__text">
                        Our facilities are built with cutting-edge technology, ensuring every car receives **dealership-quality service** without the dealership cost. From basic oil changes to full-body accident repair, **we deliver premium service with transparent pricing, genuine OEM parts, and quick turnaround**. Whether you're looking for <a href="https://autocarrepair.in/blog/5-reasons-why-periodic-maintenance-service-of-your-car-is-important/"    class="no-style-link"    rel="noopener noreferrer"    title="periodic maintenance">   periodic maintenance </a>, insurance claim assistance, or expert diagnostics, we are your go-to service partner.
                    </p>
                    <p class="about-one__content__text">
                        We help you **save more and get more**—with offers like 30% off on labor, 20% off on dent & paint, ₹500 off for first-time customers, and free doorstep pickup & drop across Delhi NCR.
                    </p>
                </div>
            </div>
        </div>
        <!-- Bottom Full-Width SEO & Benefits -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="about-one__content">
                    <h5 class="about-one__content__heading mb-3">Why Customers Across Delhi NCR Trust Us</h5>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <i class="fi fi-rr-check"></i> <strong>Authorized Partners:</strong><br>Galaxy Toyota & Hans Hyundai
                        </div>
                        <div class="col-md-4 mb-4">
                            <i class="fi fi-rr-check"></i> <strong>Multi-Brand Expertise:</strong><br>German, Japanese, Korean & Indian Brands
                        </div>
                        <div class="col-md-4 mb-4">
                            <i class="fi fi-rr-check"></i> <strong>Certified Technicians:</strong><br>Trained in OEM/OES Protocols
                        </div>
                        <div class="col-md-4 mb-4">
                            <i class="fi fi-rr-check"></i> <strong>Modern Workshops:</strong><br>100% In-House Quality Control
                        </div>
                        <div class="col-md-4 mb-4">
                            <i class="fi fi-rr-check"></i> <strong>Insurance & Cashless:</strong><br>Accident Repairs + Claim Assistance
                        </div>
                        <div class="col-md-4 mb-4">
                            <i class="fi fi-rr-check"></i> <strong>Free Pickup & Drop:</strong><br>Pan-Delhi NCR Coverage
                        </div>
                        <div class="col-md-4 mb-4">
                            <i class="fi fi-rr-check"></i> <strong>Genuine Parts:</strong><br>OEM/OES Spare Parts Only
                        </div>
                        <div class="col-md-4 mb-4">
                            <i class="fi fi-rr-check"></i> <strong>Exclusive Offers:</strong><br>30% Off Labor, ₹500 Off New Users
                        </div>
                    </div>
                    <p class="about-one__content__text mt-4">
                        With workshops in <strong>Delhi, Noida, and Gurugram </strong>, <a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a> is always nearby and ready to serve. Whether you're maintaining a daily car or repairing a luxury vehicle, **our mission is to deliver top-tier workmanship, real savings, and an experience that keeps you coming back**. <br><br>
                        <strong>Visit us once and experience why we’re Delhi’s fastest-growing, most trusted car repair chain.</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Work Process Start -->
<section class="work-process-one" style="padding: 0px 0px 120px 0px;">
    <div class="container">
        <div class="sec-title text-center">
            <h6 class="sec-title__tagline bw-split-in-right">Repair Process<span class="sec-title__tagline__border"></span></h6>
            <h3 class="sec-title__title bw-split-in-left"><a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a> Process In<br> <span>Four Basic</span> Steps</h3>
        </div>
        <div class="row">
            <div class="col-lg-12 wow fadeInUp animated" data-wow-delay="400ms">
                <div class="work-process-one__border"></div>
            </div>
        </div>
        <div class="row gutter-y-30">
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="00ms">
                <div class="work-process-one__item text-center">
                    <div class="work-process-one__item__number"></div>
                    <h4 class="work-process-one__item__title">Assessment & estimates</h4>
                    <p class="work-process-one__item__text">
                        We provide transparent assessment and accurate estimates for all repair work with no hidden charges.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="100ms">
                <div class="work-process-one__item text-center">
                    <div class="work-process-one__item__number"></div>
                    <h4 class="work-process-one__item__title">repair work</h4>
                    <p class="work-process-one__item__text">
                        Our experienced professionals perform high-quality repairs using genuine parts from OES/OEM suppliers.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                <div class="work-process-one__item text-center">
                    <div class="work-process-one__item__number"></div>
                    <h4 class="work-process-one__item__title">cleaning and detailing</h4>
                    <p class="work-process-one__item__text">
                        We provide premium detailing services to restore your vehicle's appearance to like-new condition.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="00ms">
                <div class="work-process-one__item text-center">
                    <div class="work-process-one__item__number"></div>
                    <h4 class="work-process-one__item__title">quality check & pickup</h4>
                    <p class="work-process-one__item__text">
                        Final quality inspection before returning your vehicle, ensuring complete satisfaction.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Work Process End -->
<section class="faq-one faq-one--home-two">
    <div class="faq-one--home-two__bg" style="background-image: url(front/images/shapes/faq-bg-1-home-two.png);"></div>
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="faq-one__image">
                    <img class="wow fadeInLeft" data-wow-delay="100ms" src="front/images/resources/faq-1-1.jpg" alt="karoons">
                    <img class="wow fadeInLeft" data-wow-delay="300ms" src="front/images/resources/faq-1-2.jpg" alt="karoons">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="faq-one__accordion karoons-accrodion" data-grp-name="karoons-accrodion">
                    <div class="sec-title text-left">
                        <h6 class="sec-title__tagline bw-split-in-right">Faq<span class="sec-title__tagline__border"></span></h6>
                        <h3 class="sec-title__title bw-split-in-left">Frequently Asked <span>Question</span></h3>
                    </div>
                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                What brands do you service?
                                <span class="accrodion-title__icon"></span>
                            </h4>
                        </div>
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>
                                    We specialize in both luxury and volume brands including:<br>
                                    <strong>Luxury:</strong> Mercedes, Audi, BMW, Lexus, Volvo, Range Rover, Jaguar.<br>
                                    <strong>Volume brands:</strong> VW, Hyundai, Kia, Toyota, and more.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="accrodion active">
                        <div class="accrodion-title">
                            <h4>
                                Why choose an independent service center?
                                <span class="accrodion-title__icon"></span>
                            </h4>
                        </div>
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>
                                    As an independent workshop, we offer several advantages: competitive pricing without franchise markups, personalized service, and the flexibility to use both OEM and high-quality aftermarket parts based on your preference and budget.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                What makes your pricing competitive?
                                <span class="accrodion-title__icon"></span>
                            </h4>
                        </div>
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>
                                    We operate our own workshop which gives us complete control over quality and pricing. With no middlemen or franchise fees, we pass on the savings directly to you while maintaining the highest service standards.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="accrodion">
                        <div class="accrodion-title">
                            <h4>
                                What services do you offer?
                                <span class="accrodion-title__icon"></span>
                            </h4>
                        </div>
                        <div class="accrodion-content">
                            <div class="inner">
                                <p>
                                    Our comprehensive services include general maintenance, accident repairs, vehicle detailing/beautification, refurbishment of pre-owned cars, premium accessories, DIY products, and extended warranty options - all performed by our expert technicians.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="testimonials-one">
    <div class="testimonials-one__shape" style="background-image: url(front/images/resources/testimonial-1-shape-1.png);"></div>
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
                                    <a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a> gave my BMW perfect care in Delhi NCR. Their skilled team used genuine parts and delivered exceptional service with complete transparency. Highly recommended
                                </p>
                                <div class="testimonials-card__author">
                                    <div class="testimonials-card__image">
                                        <img src="front/images/resources/testi-1-1.jpg" alt="Rahul Mehta">
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
                                    My Mercedes got top-notch service at <a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a>. Their Noida workshop has expert technicians who deliver dealership quality at much better prices.Truly impressive
                                </p>
                                <div class="testimonials-card__author">
                                    <div class="testimonials-card__image">
                                        <img src="front/images/resources/testi-1-2.jpg" alt="Priya Sharma">
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
                                    The dent repair on my Hyundai at <a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a> Gurugram was flawless. They offered fair pricing and completed the job faster than authorized service centers
                                </p>
                                <div class="testimonials-card__author">
                                    <div class="testimonials-card__image">
                                        <img src="front/images/resources/testi-1-3.jpg" alt="Vikram Singh">
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
                                    <a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a> handled my Audi's insurance claim perfectly. Their Delhi workshop made the entire process smooth and stress-free with professional service
                                </p>
                                <div class="testimonials-card__author">
                                    <div class="testimonials-card__image">
                                        <img src="front/images/resources/testi-1-4.jpg" alt="Neha Kapoor">
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
                                    My Land Rover's ceramic coating at <a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a> looks stunning. Their Okhla center offers premium services at reasonable prices with excellent results
                                </p>
                                <div class="testimonials-card__author">
                                    <div class="testimonials-card__image">
                                        <img src="front/images/resources/testi-1-5.jpg" alt="Arjun Malhotra">
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
                                    <a href="https://autocarrepair.in"    class="no-style-link"    rel="noopener noreferrer"    title="Auto Car Repair">   Auto Car Repair </a>'s maintenance package for my Toyota was comprehensive and affordable. Their expert technicians in Noida delivered perfect service as promised
                                </p>
                                <div class="testimonials-card__author">
                                    <div class="testimonials-card__image">
                                        <img src="front/images/resources/testi-1-6.jpg" alt="Sanjay Gupta">
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
                            <h3 class="sec-title__title bw-split-in-left">Delhi NCR <span>drivers trust</span> us</h3>
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