@extends('front.layout.main')

@section('content')
<section class="page-header">
    <div class="page-header__bg"></div>
    <div class="container">
        <h1 class="page-header__title bw-split-in-right">Our location</h1>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{url('/')}}"><i class="flaticon-home"></i>Home</a></li>
            <li><span>Service Center</span></li>
        </ul>
    </div>
</section>

<style>
    .custom-select-container {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .custom-select-label {
        position: absolute;
        top: -10px;
        left: 24px;
        background: #fff;
        padding: 0 5px;
        font-size: 14px;
        color: #333;
        z-index: 1;
    }

    .form-select {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
        border: 1px solid #ced4da;
    }

    .form-select:focus {
        border-color: #0d6efd;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    @media (max-width: 576px) {
        .form-select {
            font-size: 0.9rem;
        }
    }
</style>
<section class="contact">
    <div class="container">
        <div class="row">
            <!-- Contact Info Column -->
            <div class="sec-title text-left">
                        <h6 class="sec-title__tagline bw-split-in-right">
                        SERVICE CENTERS <span class="sec-title__tagline__border"></span>
                        </h6>
                        <h3 class="sec-title__title bw-split-in-left">
                        NEED ASSISTANCE? <br><span>FIND A SERVICE CENTER!</span>
                        </h3>
                    </div>
                    <div class="custom-select-container mb-4">
                        <label for="locationSelect" class="custom-select-label">
                            <i class="fas fa-map-marker-alt me-1"></i> Select Location
                        </label>
                        <select id="locationSelect" class="form-select">
                            <option value="motiNagar">Moti Nagar</option>
                            <option value="gurugram">Gurugram</option>
                            <option value="noida">Noida</option>
                            <option value="okhla">Okhla</option>
                            <!--<option value="badli">Badli</option>-->
                            <!--<option value="karnal">Karnal</option>-->
                            <!--<option value="faridabad">Faridabad</option>-->
                            <!--<option value="ghaziabad">Ghaziabad</option>-->
                        </select>
                    </div>
            <div class="col-lg-6 d-flex align-items-center">
                <div class="contact__content">
                    

                    <!-- Location Dropdown -->
                    

                    <!-- Contact Details -->
                    <div class="contact__info">
                        <div class="contact__info__icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="contact__info__content">
                            <h5 class="contact__info__title">Address</h5>
                            <p id="addressText" class="contact__info__text"></p>
                        </div>
                    </div>

                    <div class="contact__info">
                        <div class="contact__info__icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="contact__info__content">
                            <h5 class="contact__info__title">Quick Contact</h5>
                            <p id="phoneText" class="contact__info__text"></p>
                        </div>
                    </div>

                    <div class="contact__info">
                        <div class="contact__info__icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact__info__content">
                            <h5 class="contact__info__title">Support Email</h5>
                            <p class="contact__info__text">
                                <a href="mailto:info@autocarrepair.in">support@autocarrepair.in</a>
                            </p>
                        </div>
                    </div>

                    <div class="contact__info">
                        <div class="contact__info__icon"><i class="fab fa-whatsapp"></i></div>
                        <div class="contact__info__content">
                            <h5 class="contact__info__title">WhatsApp</h5>
                            <p id="whatsappText" class="contact__info__text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Column -->
            <div class="col-lg-6">
                <img id="locationImage" src="resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png" alt="Location Image" class="img-fluid rounded shadow" />
            </div>
        </div>
    </div>
</section>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<!-- JavaScript to handle dynamic content -->
<script>
    const locationData = {
        motiNagar: {
            address: "59, Najafgarh Road Industrial Area, Rama Road New Delhi-110015",
            phone: "+91 9870400861",
            whatsapp: "https://api.whatsapp.com/send?phone=9870400861",
            image: "resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png"
        },
        gurugram: {
            address: "Unit-1 Plot No 29 & 30, near Kargil Shaheed Sukhbir Singh Yadav Marg, Info Technology Park, Sector 34, Gurugram, Haryana 122001",
            phone: "+91 9810446692",
            whatsapp: "https://api.whatsapp.com/send?phone=9810446692",
            image: "resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png"
        },
        noida: {
            address: "H-142 sector 63 noida, Near Ananda Corporate Office",
            phone: "+91 9773994175",
            whatsapp: "https://api.whatsapp.com/send?phone=9870400861",
            image: "resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png"
        },
        okhla: {
            address: "G7JF+F87, Pocket W, Okhla Phase II, Delhi 110020",
            phone: "9289200643",
            whatsapp: "https://api.whatsapp.com/send?phone=9870400861",
            image: "resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png"
        },
        badli: {
            address: "Coming Soon",
            phone: "Soon",
            whatsapp: "https://api.whatsapp.com/send?phone=9870400861",
            image: "resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png"
        },
        karnal: {
            address: "Coming Soon",
            phone: "Soon",
            whatsapp: "https://api.whatsapp.com/send?phone=9870400861",
            image: "resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png"
        },
        faridabad: {
            address: "Coming Soon",
            phone: "Soon",
            whatsapp: "https://api.whatsapp.com/send?phone=9870400861",
            image: "resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png"
        },
        ghaziabad: {
            address: "Coming Soon",
            phone: "Soon",
            whatsapp: "https://api.whatsapp.com/send?phone=9870400861",
            image: "resources/views/front/servicecenter/files/img/ChatGPT Image Apr 21, 2025, 03_57_39 PM (1).png"
        }
    };

    const locationSelect = document.getElementById("locationSelect");
    const addressText = document.getElementById("addressText");
    const phoneText = document.getElementById("phoneText");
    const whatsappText = document.getElementById("whatsappText");
    const locationImage = document.getElementById("locationImage");

    function updateContactInfo(locationKey) {
        const data = locationData[locationKey];
        addressText.textContent = data.address;
        phoneText.innerHTML = `<a href="tel:${data.phone.replace(/\s+/g, '')}">${data.phone}</a>`;
        whatsappText.innerHTML = `<a href="${data.whatsapp}" target="_blank">${data.phone}</a>`;
        locationImage.src = data.image;
    }

    // On initial load
    updateContactInfo(locationSelect.value);

    // On dropdown change
    locationSelect.addEventListener("change", function () {
        updateContactInfo(this.value);
    });
</script>
        <section class="contact-map">
            <div class="google-map google-map__contact">
            <iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d224308.23870093664!2d77.0306154788115!3d28.545242899595205!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sAuto%20Car%20Repair%20(ACR)!5e0!3m2!1sen!2sin!4v1745216013343!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <!-- /.google-map -->
        </section><!-- /.contact-map -->

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
            
                    <div class="form-one__control form-one__control--full">
                        <label for="message">Write Message</label>
                        <textarea id="message" name="message" placeholder=""></textarea>
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
