@extends('front.layout.main')
@section('meta')
    @if(isset($meta_title) && !empty($meta_title))
        <title>{{ $meta_title }}</title>
    @else
        <title>Auto Car Repair {{ ucfirst(str_replace('-', ' ', $location)) }}</title>
    @endif
    
    @if(isset($meta_description) && !empty($meta_description))
        <meta name="description" content="{{ $meta_description }}">
    @endif
    
    @if(isset($meta_keywords) && !empty($meta_keywords))
        <meta name="keywords" content="{{ $meta_keywords }}">
    @endif
@endsection
@section('content')

<section class="page-header">
  <div class="page-header__bg"></div>
  <!-- /.page-header__bg -->
  <div class="container">
    <h1 class="page-header__title bw-split-in-right"><a href="https://autocarrepair.in"
   class="no-style-link"
   rel="noopener noreferrer"
   title="Auto Car Repair">
  Auto Car Repair
</a>
 (ACR) Moti Nagar</h1>
    <ul class="karoons-breadcrumb list-unstyled">
      <li>
        <a href="{{ url('/') }}"><i class="flaticon-home"></i>Home</a>
      </li>
      <li><span>Moti Nagar</span></li>
    </ul>
    <!-- /.thm-breadcrumb list-unstyled -->
  </div>
  <!-- /.container -->
</section>
<!-- /.page-header -->
<section class="work-process-one">
  <div class="container">
    <div class="sec-title text-center">
      <h6 class="sec-title__tagline bw-split-in-right">
        <a href="https://autocarrepair.in"
   class="no-style-link"
   rel="noopener noreferrer"
   title="Auto Car Repair">
  Auto Car Repair </a> Moti Nagar<span
          class="sec-title__tagline__border"
        ></span>
      </h6>
      <!-- /.sec-title__tagline -->
      <h3 class="sec-title__title bw-split-in-left">
        Delhi NCR's Trusted Multi-Brand<br />
        <span>Car Service</span> Center
      </h3>
      <!-- /.sec-title__title -->
    </div>
    <!-- /.sec-title -->

    <!-- Added About Us paragraph -->
    <div class="row wow fadeInUp" data-wow-delay="00ms">
      <div class="col-lg-12">
        <p style="text-align: center; margin-bottom: 20px">
          <strong><a href="https://autocarrepair.in"
   class="no-style-link"
   rel="noopener noreferrer"
   title="Auto Car Repair">
  Auto Car Repair
</a>
 (Powered by Autogine Services)</strong> offers
          exceptional car maintenance, repairs, and detailing services in Delhi
          NCR. Our modern <a href="https://autocarrepair.in/car-service-moti-nagar"
   class="no-style-link"
   rel="noopener noreferrer"
   title="workshop in Moti Nagar">
  workshop in Moti Nagar
</a>
 is equipped with advanced
          diagnostic tools, state-of-the-art equipment, and genuine OEM/OES
          products. Our expert technicians provide services for leading brands
          like BMW, Audi, Mercedes-Benz, Jaguar, Land Rover, Toyota, Hyundai and
          more — covering regular servicing, advanced detailing, and complex
          mechanical repairs.
        </p>
        <div class="sec-title text-center">
          <h4 class="work-process-one__item__title">
            At <a href="https://autocarrepair.in"
   class="no-style-link"
   rel="noopener noreferrer"
   title="Auto Car Repair">
  Auto Car Repair
</a>
, we are committed to:
          </h4>
          <div class="row">
            <div class="col-lg-12 wow fadeInUp animated" data-wow-delay="400ms">
              <div class="work-process-one__border"></div>
            </div>
          </div>
          <div class="row gutter-y-30">
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="00ms">
              <div class="work-process-one__item text-center">
                <div class="work-process-one__item__number"></div>
                <!-- /.work-process-number -->
                <h4 class="work-process-one__item__title">
                  Fair & Transparent Pricing
                </h4>

                <!-- /.work-process-text -->
              </div>
              <!-- /.work-process-item -->
            </div>
            <!-- item -->
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="100ms">
              <div class="work-process-one__item text-center">
                <div class="work-process-one__item__number"></div>
                <!-- /.work-process-number -->
                <h4 class="work-process-one__item__title">
                  OEM/OES Parts Only
                </h4>

                <!-- /.work-process-text -->
              </div>
              <!-- /.work-process-item -->
            </div>
            <!-- item -->
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="200ms">
              <div class="work-process-one__item text-center">
                <div class="work-process-one__item__number"></div>
                <!-- /.work-process-number -->
                <h4 class="work-process-one__item__title">Timely Updates</h4>

                <!-- /.work-process-text -->
              </div>
              <!-- /.work-process-item -->
            </div>
            <!-- item -->
            <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="00ms">
              <div class="work-process-one__item text-center">
                <div class="work-process-one__item__number"></div>
                <!-- /.work-process-number -->
                <h4 class="work-process-one__item__title">Quality Checks</h4>

                <!-- /.work-process-text -->
              </div>
              <!-- /.work-process-item -->
            </div>
            <!-- item -->
          </div>
          <p style="text-align: center">
            Our <strong>Moti Nagar Service Center</strong> ensures your vehicle
            performs at its best. Book your car service for long-lasting
            performance and maximum safety.
          </p>
        </div>
        <style>
          .work-process-one__item__title {
            font-size: 15px;
            line-height: 28px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 10px 0 9px;
          }
          .work-process-one__item__number {
            font-size: 15px;
            width: 35px;
            height: 35px;
          }
          .work-process-one__border {
            top: 20px;
          }
        </style>
      </div>
    </div>
  </div>
</section>

<section class="contact">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 d-flex align-items-center">
        <div class="contact__content">
          <div class="sec-title text-left">
            <h6 class="sec-title__tagline bw-split-in-right">
              get in touch<span class="sec-title__tagline__border"></span>
            </h6>
            <!-- /.sec-title__tagline -->

            <h3 class="sec-title__title bw-split-in-left">
              Have Questions? <br /><span>Get In Touch!</span>
            </h3>
            <!-- /.sec-title__title -->
          </div>
          <!-- /.sec-title -->
          <div class="contact__info">
            <div class="contact__info__icon">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="contact__info__content">
              <h5 class="contact__info__title">Address</h5>
              <p class="work-process-one__item__title">
                                  <a href="https://share.google/lljREHPxgdb4i4j0a" target="_blank" >
                59, Najafgarh Road Industrial, Area Rama Road New Delhi-110015
              </p>
            </div>
          </div>
          <!-- item -->
          <div class="contact__info">
            <div class="contact__info__icon">
              <i class="fas fa-phone-alt"></i>
            </div>
            <div class="contact__info__content">
              <h5 class="contact__info__title">Quick Contact</h5>
              <p class="work-process-one__item__title">
                <a href="tel:+919870400861">+91 9870400861</a><br />
              </p>
            </div>
          </div>
          <!-- item -->
          <div class="contact__info">
            <div class="contact__info__icon">
              <i class="fas fa-envelope"></i>
            </div>
            <div class="contact__info__content">
              <h5 class="contact__info__title">Support Email</h5>
              <p class="work-process-one__item__title">
                <a href="mailto:support@autocarrepair.in"
                  >support@autocarrepair.in</a
                >
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <form
          class="contact-one__form contact-form-validated form-one wow fadeInUp"
          method="POST"
          action="{{ route('enquiry.submit') }}"
          id="compny-form"
          enctype="multipart/form-data"
          data-parsley-validate=""
        >
          @csrf
          <div class="contact-one__form__dot-one"></div>
          <div class="contact-one__form__dot-two"></div>
          <div class="contact-one__form__dot-three"></div>
          <div class="contact-one__form__dot-four"></div>

          <div class="form-one__group">
            <div class="form-one__control form-one__control--full">
              <input
                id="first-name"
                type="text"
                name="name"
                required
                placeholder="Enter Your Name"
              />
            </div>

            <div class="form-one__control form-one__control--full">
              <input
                id="email"
                type="email"
                name="email"
                required
                placeholder="Enter Your Email"
              />
            </div>

            <div class="form-one__control form-one__control--full">
              <div class="form-one__control__select">
                <label class="sr-only" for="location">Select Location</label>
                <select
                  name="location"
                  id="location"
                  required
                  class="selectpicker"
                  style="
                    background-color: #f4f4f4;
                    color: #838383;
                    height: 50px;
                    width: 100%;
                    padding: 0 30px;
                    border: none;
                  "
                >
                  <option selected disabled>Select Location</option>
                  <option value="ACR Motinagar">Motinagar</option>
                  <option value="ACR Gurgaon">Gurgaon</option>
                  <option value="ACR Noida">Noida</option>
                  <option value="ACR Okhla">Okhla</option>
                </select>
              </div>
            </div>

            <div class="form-one__control form-one__control--full">
              <input
                type="tel"
                name="phone"
                id="phone_number"
                required
                maxlength="10"
                placeholder="Enter Your Phone Number"
                pattern="\d{10}"
                title="Please enter a 10-digit phone number"
              />
            </div>
            <!-- HONEYPOT FIELDS (hidden from users, visible to bots) -->
            <div
              style="
                position: absolute;
                left: -9999px;
                opacity: 0;
                pointer-events: none;
              "
            >
              <input
                type="text"
                name="website"
                tabindex="-1"
                autocomplete="off"
              />
              <input type="url" name="url" tabindex="-1" autocomplete="off" />
            </div>
            <!-- Phone Restriction Script -->
            <script>
              document.addEventListener("DOMContentLoaded", function () {
                var numberInput = document.getElementById("phone_number");
                numberInput.addEventListener("input", function () {
                  var inputValue = numberInput.value.replace(/\D/g, "");
                  numberInput.value = inputValue.slice(0, 10);
                });
              });
            </script>

            <!--<div class="form-one__control form-one__control--full">-->
            <!--  <textarea name="message" placeholder="Write Message"></textarea>-->
            <!--</div>-->

            <!-- Hidden UTM Fields -->
            <input type="hidden" name="utm_source" value="" />
            <input type="hidden" name="utm_medium" value="" />
            <input type="hidden" name="utm_campaign" value="" />
            <input type="hidden" name="utm_term" value="" />
            <input type="hidden" name="utm_content" value="" />

            <!-- Captcha -->
            <div class="captcha-container">
              <label class="captcha-question">Loading captcha...</label>
              <input
                type="text"
                name="captcha"
                class="captcha-answer"
                placeholder="Enter answer"
                required
                style="width: auto"
              />
              <input
                type="hidden"
                name="correct_answer"
                class="correct-answer"
              />
            </div>

            <!-- Agreement -->
            <div class="form-one__control form-one__control--full">
              <div class="agree-line">
                <input type="checkbox" id="agree" name="agree" required />
                <label for="agree"
                  >I agree to receive calls, e-mail, WhatsApp messages, and SMS
                  from ACR.</label
                >
              </div>
            </div>

            <!-- Submit Button -->
            <div class="form-one__control form-one__control--full">
              <button type="submit" class="karoons-btn">
                <span>Submit <i class="fas fa-paper-plane"></i></span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section class="gallery-page">
  <div class="container">
    <div
      class="gallery-page__carousel karoons-owl__carousel karoons-owl__carousel--basic-nav owl-carousel owl-theme"
      data-owl-options='{
            "items": 1,
            "margin": 0,
            "loop": false,
            "smartSpeed": 700,
            "nav": true,
            "navText": ["<i class=\"fi fi-rr-arrow-up-right\"></i>","<i class=\"fi fi-rr-arrow-up-right\"></i>"],
            "dots": false,
            "autoplay": true,
            "responsive": {
                "0": {
                    "items": 1
                },
                "768": {
                    "items": 2,
                    "margin": 30
                },
                "992": {
                    "items": 3,
                    "margin": 30
                }
            }
        }'
    >
      <div class="item">
        <div class="gallery-page__card">
          <img
            src="resources/views/front/servicecenter/motinagar/assets/image/locations-banner/motinagar1.jpeg"
            alt="karoons"
          />
        </div>
        <!-- /.gallery-page__card -->
      </div>
      <!-- /.item -->
      <div class="item">
        <div class="gallery-page__card">
          <img
            src="resources/views/front/servicecenter/motinagar/assets/image/locations-banner/motinagar2.jpeg"
            alt="karoons"
          />
        </div>
        <!-- /.gallery-page__card -->
      </div>
      <!-- /.item -->
      <div class="item">
        <div class="gallery-page__card">
          <img
            src="resources/views/front/servicecenter/motinagar/assets/image/locations-banner/motinagr5.jpeg"
            alt="karoons"
          />
        </div>
        <!-- /.gallery-page__card -->
      </div>
      <!-- /.item -->
      <div class="item">
        <div class="gallery-page__card">
          <img
            src="resources/views/front/servicecenter/motinagar/assets/image/locations-banner/motinagr4.jpeg"
            alt="karoons"
          />
        </div>
        <!-- /.gallery-page__card -->
      </div>
      <!-- /.item -->
      <div class="item">
        <div class="gallery-page__card">
          <img
            src="resources/views/front/servicecenter/motinagar/assets/image/locations-banner/motinagar3.jpeg"
            alt="karoons"
          />
        </div>
        <!-- /.gallery-page__card -->
      </div>
      <!-- /.item -->
    </div>
    <!-- /.row -->
  </div>
  <!-- /.container -->
</section>
<!-- /.gallery-page -->

<section class="contact-map">
  <div class="google-map google-map__contact">
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3501.0365427704505!2d77.15023877529008!3d28.658624475650456!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d0331cdd62da9%3A0xdac227ddc17a8031!2sAuto%20Car%20Repair%20(ACR)!5e0!3m2!1sen!2sin!4v1753679438510!5m2!1sen!2sin"
      width="800"
      height="600"
      style="border: 0"
      allowfullscreen=""
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      class="map__contact"
    ></iframe>
  </div>
  <!-- /.google-map -->
</section>
<!-- /.contact-map -->

@endsection
