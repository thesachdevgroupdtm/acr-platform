@extends('front.layout.main')

@section('content')
<section class="page-header">
    <div class="page-header__bg"></div>
    <div class="container">
        <h1 class="page-header__title bw-split-in-right">Exclusive Coupons</h1>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{url('/')}}"><i class="flaticon-home"></i>Home</a></li>
            <li><span>Coupons</span></li>
        </ul>
    </div>
</section><style>
        /* Popup Form Styles */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            display: table;
            height: 100%;
            width: 100%;
            transform: scale(0);
            visibility: hidden;
        }

        .popup-overlay.active {
            visibility: visible;
        }

        /* Unfolding animation for popup overlay */
        .popup-overlay.one {
            transform: scaleY(0.01) scaleX(0);
            animation: unfoldIn 1s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

        .popup-overlay.one.out {
            transform: scale(1);
            animation: unfoldOut 1s 0.3s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

        .popup-form-container {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            margin: 0 auto;
            transform: scale(0);
        }

        .popup-overlay.one .popup-form-container {
            transform: scale(0);
            animation: zoomIn 0.5s 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

        .popup-overlay.one.out .popup-form-container {
            animation: zoomOut 0.5s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

        /* Modal background styling */
        .popup-overlay {
            background: rgba(0, 0, 0, 0.8);
            text-align: center;
            vertical-align: middle;
        }

        /* Make sure the form is centered */
        .popup-overlay {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Ensure body doesn't scroll when modal is active */
        html.modal-active, body.modal-active {
            overflow: hidden;
        }

        .popup-form-header {
            background-color: #005EFF;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .popup-form-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .popup-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .popup-close-btn:hover {
            transform: rotate(90deg);
        }

        .popup-form-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #005EFF;
            outline: none;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
        }

        .form-group.half-width {
            flex: 1;
            margin-right: 10px;
        }

        .form-group.half-width:last-child {
            margin-right: 0;
        }

        @media (max-width: 576px) {
            .form-group.half-width {
                flex: 100%;
                margin-right: 0;
            }
        }

        

        

        .error-message {
            color: #005EFF;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        /* Animation Keyframes */
        @keyframes unfoldIn {
            0% {
                transform: scaleY(0.005) scaleX(0);
            }
            50% {
                transform: scaleY(0.005) scaleX(1);
            }
            100% {
                transform: scaleY(1) scaleX(1);
            }
        }

        @keyframes unfoldOut {
            0% {
                transform: scaleY(1) scaleX(1);
            }
            50% {
                transform: scaleY(0.005) scaleX(1);
            }
            100% {
                transform: scaleY(0.005) scaleX(0);
            }
        }

        @keyframes zoomIn {
            0% {
                transform: scale(0);
            }
            100% {
                transform: scale(1);
            }
        }

        @keyframes zoomOut {
            0% {
                transform: scale(1);
            }
            100% {
                transform: scale(0);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .popup-form-container {
                width: 95%;
            }

            .popup-form-body {
                padding: 15px;
            }
        }

        /* Additional Styles */
        .banner {
            margin-bottom: 20px;
        }

        .banner img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .coupon-filter {
            margin-bottom: 20px;
        }

        .filter-tabs {
            display: flex;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .tab {
            flex: 1;
            padding: 12px 0;
            text-align: center;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab.active {
            border-bottom: 2px solid #e53e3e;
            color: #e53e3e;
        }

        .count {
            color: #777;
            margin-left: 4px;
        }

        .coupon-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding-bottom: 20px;
        }

        .coupon-card {
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        @media (min-width: 768px) {
            .coupon-card {
                flex-direction: row;
            }
        }

        .coupon-logo {
            padding: 0px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        @media (min-width: 768px) {
            .coupon-logo {
                width: 30%;
                border-bottom: none;
                border-right: 1px solid #eee;
            }
        }

        .coupon-logo img {
            max-width: 100%;
            height: auto;
        }

        .coupon-content {
            padding: 16px;
            flex: 1;
        }

        @media (min-width: 768px) {
            .coupon-content {
                width: 60%;
            }
        }

        .coupon-title {
            font-size: 18px;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .coupon-description {
            font-size: 14px;
            color: #666;
        }

        .more-link a,
        .less-link {
            color: #e53e3e;
            text-decoration: none;
            margin-left: 4px;
            cursor: pointer;
        }

        .more-link a:hover,
        .less-link:hover {
            text-decoration: underline;
        }

        .coupon-action {
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f9f9f9;
        }

        @media (min-width: 768px) {
            .coupon-action {
                width: 20%;
                justify-content: center;
            }
        }

        .coupon-button {
            display: inline-block;
            vertical-align: middle;
            -webkit-appearance: none;
            border: none;
            outline: none !important;
            background-color: var(--karoons-base, #005EFF);
            color: var(--karoons-white, #fff);
            font-size: 16px;
            font-weight: 600;
            padding: 9px 21px;
            transition: 0.5s ease-in-out;
            text-transform: uppercase;
            position: relative;
            z-index: 2;
            overflow: hidden;
            text-align: center;
            cursor: pointer;
        }

        .button-text {
            position: relative;
            z-index: 2;
            transition: opacity 0.2s ease;
        }

        .code-text {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            color: #e53e3e;
            transform: translateY(100%);
            transition: transform 0.2s ease;
        }

        .coupon-button:hover .button-text {
            opacity: 0;
        }

        .coupon-button:hover .code-text {
            transform: translateY(0);
            background-color: var(--karoons-black, #212226);
            color: var(--karoons-white, #fff);
        }

        .expiry-text {
            font-size: 12px;
            color: #777;
        }

        .copied-notification {
            position: fixed;
            bottom: 10px;
            right: 20px;
            background-color: #4caf50;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s, transform 0.3s;
        }

        .copied-notification.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Book Now Popup Form -->
    <div class="popup-overlay" id="bookNowPopup">
        <div class="popup-form-container">
            <div class="popup-form-header">
                <h3 class="popup-form-title">Book Service Appointment</h3>
                <button class="popup-close-btn" id="closePopupBtn">&times;</button>
            </div>
            <div class="popup-form-body">
<form id="bookNowForm" method="POST" 
      action="{{ route('enquiry.submit') }}"  enctype="multipart/form-data">
    <!-- Name and Phone -->
    <div class="form-row">
        <div class="form-group half-width">
            <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
            <div class="error-message" id="nameError">Please enter your full name</div>
        </div>
        <div class="form-group half-width">
            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter Valid 10-digit phone No" maxlength="10" pattern="\d{10}" title="Please enter a 10-digit phone number" required>
            <div class="error-message" id="phoneError">Please enter a valid 10-digit phone number</div>
        </div>
    </div>

    <!-- Email and Location -->
    <div class="form-row">
        <div class="form-group half-width">
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
            <div class="error-message" id="emailError">Please enter a valid email address</div>
        </div>
        <div class="form-group half-width">
            <select class="form-control" id="location" name="location" required>
                <option value="" disabled selected>Select Location</option>
                <option value="ACR Motinagar">Motinagar</option>
                <option value="ACR Gurgaon">Gurgaon</option>
                <option value="ACR Noida">Noida</option>
                <option value="ACR Okhla">Okhla</option>
            </select>
            <div class="error-message" id="locationError">Please select your location</div>
        </div>
    </div>

    <!-- Offer Name -->
    <input type="hidden" id="offerNameInput" name="offer_name">
    <div class="form-group">
        <input type="text" class="form-control" id="offerNameDisplay" placeholder="Offer Name" readonly required>
    </div>
 <!-- HONEYPOT FIELDS (hidden from users, visible to bots) -->
        <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
            <input type="url" name="url" tabindex="-1" autocomplete="off">
        </div>
    <!-- CAPTCHA Section -->
    <div class="booking-form-group">
        <div class="captcha-container">
            <label class="captcha-question">Loading captcha...</label>
            <input type="text" class="captcha-answer" name="captcha" placeholder="Enter answer" required style="background-color: #80808033;">
            <input type="hidden" class="correct-answer" name="correct_answer">
        </div>
        <div class="error-message" id="captcha_error"></div>
    </div>

    <!-- Consent Checkbox -->
    <div class="form-group w-100 text-left d-flex align-items-start banner_form_check">
        <input type="checkbox" name="agree" required style="margin-top: 3px; margin-right: 6px;" />
        <small>I agree to receive calls, emails, WhatsApp messages, and SMS from ACR.</small>
    </div>

    <!-- Hidden UTM Fields -->
    <input type="hidden" name="utm_source" value="">
    <input type="hidden" name="utm_medium" value="">
    <input type="hidden" name="utm_campaign" value="">
    <input type="hidden" name="utm_term" value="">
    <input type="hidden" name="utm_content" value="">

    <!-- Form Type -->
    <input type="hidden" name="form_type" value="offer">

    <!-- Submit Button -->
    <button type="submit" class="karoons-btn" id="submitBtn">Submit Booking Request</button>

    <!-- Success Message -->
    <div class="success-message" style="display:none; color:green; margin-top:10px;">
        Your booking request has been submitted successfully!
    </div>
</form>
            </div>
        </div>
    </div>

    <section class="portfolio-one" id="acr-coupons">
        <div class="container">
            <div class="sec-title text-center">
                <h6 class="sec-title__tagline bw-split-in-right">
                    Our Coupons<span class="sec-title__tagline__border"></span>
                </h6>
                <h3 class="sec-title__title bw-split-in-left">
                    Exclusive <span>Car Service</span> Coupons
                </h3>
            </div>
            <div class="coupon-list">
                <!-- Coupon Card 1 -->
                <div class="coupon-card">
                    <div class="coupon-logo">
                        <img src="resources/views/front/service-coupons/coupons/acservice.jpg" alt="ACR Service Coupons" loading="lazy">
                    </div>
                    <div class="coupon-content">
                        <h3 class="coupon-title">Car AC Service Offer - Get 20% Off.</h3>
                        <div class="coupon-description">
                            <div class="coupon-text">Experience expert car AC service at Auto Car Repair (myTVS). Avail of comprehensive inspection and servicing, ensuring optimal cooling performance. Our certified technicians provide refrigerant checks, leak detection, and filter replacements. Book your car AC service now! T&C Apply.</div>
                        </div>
                    </div>
                    <div class="coupon-action">
                        <button class="coupon-button v3-action-btn bookNowButton" data-code="COOL20" onclick="openForm()" data-utm="Coupon Applied- *COOL20* Car AC Service Offer - Get 20% Off">
                            <span class="button-text">GET COUPON</span>
                            <span class="code-text">COOL20</span>
                        </button>
                        <p class="expiry-text">Expires 4 weeks left</p>
                    </div>
                </div>

                <!-- Coupon Card 2 -->
                <div class="coupon-card">
                    <div class="coupon-logo">
                        <img src="resources/views/front/service-coupons/coupons/battery.jpg" alt="ACR Service Coupons" loading="lazy">
                    </div>
                    <div class="coupon-content">
                        <h3 class="coupon-title">Car Battery Replacement Offer - Rs. 500 Off.</h3>
                        <div class="coupon-description">
                            <div class="coupon-text">Is your car battery giving you trouble? Visit Auto Car Repair (myTVS) and get your car battery replaced by our experts. We offer genuine parts and expert servicing by certified technicians. Book your car service today! T&C Apply.</div>
                        </div>
                    </div>
                    <div class="coupon-action">
                        <button class="coupon-button v3-action-btn bookNowButton" data-code="Coupon Applied- *BATTERY500* Rs. 500 Off on Car Battery Replacement Offer." onclick="openForm()" data-utm="BATTERY500">
                            <span class="button-text">GET COUPON</span>
                            <span class="code-text">BATTERY500</span>
                        </button>
                        <p class="expiry-text">Expires 2 Weeks left</p>
                    </div>
                </div>

                <!-- Coupon Card 3 -->
                <div class="coupon-card">
                    <div class="coupon-logo">
                        <img src="resources/views/front/service-coupons/coupons/dentpaint.jpg" alt="ACR Service Coupons" loading="lazy">
                    </div>
                    <div class="coupon-content">
                        <h3 class="coupon-title">Car Dent & Paint Offer - Get 20% Off.</h3>
                        <div class="coupon-description">
                            <div class="coupon-text">Get rid of dents and scratches with our top-notch dent removal and painting services. Our expert technicians use advanced techniques and top-quality products to restore your car’s flawless appearance. Book your car service today! T&C Apply.</div>
                        </div>
                    </div>
                    <div class="coupon-action">
                        <button class="coupon-button v3-action-btn bookNowButton" data-code="PAINT20" onclick="openForm()" data-utm="Coupon Applied- *PAINT20* Get 20% Off-Car Dent & Paint Offer">
                            <span class="button-text">GET COUPON</span>
                            <span class="code-text">PAINT20</span>
                        </button>
                        <p class="expiry-text">Expires 1 Week left</p>
                    </div>
                </div>

                <!-- Coupon Card 4 -->
                <div class="coupon-card">
                    <div class="coupon-logo">
                        <img src="resources/views/front/service-coupons/coupons/labour.jpg" alt="ACR Service Coupons" loading="lazy">
                    </div>
                    <div class="coupon-content">
                        <h3 class="coupon-title">Car Service Offer - Get 30% Off on Labor Charge.</h3>
                        <div class="coupon-description">
                            <div class="coupon-text">Maintain your vehicle's optimal performance and safety with our expert car service. We offer thorough oil changes, brake inspections, fluid checks, and more at Auto Car Repair (myTVS). Book your car service today! T&C Apply.</div>
                        </div>
                    </div>
                    <div class="coupon-action">
                        <button class="coupon-button v3-action-btn bookNowButton" data-code="SERVICE30" onclick="openForm()" data-utm="Coupon Applied- *SERVICE30* Get 30% Off on Labor Charge">
                            <span class="button-text">GET COUPON</span>
                            <span class="code-text">SERVICE30</span>
                        </button>
                        <p class="expiry-text">Expires 4 Weeks left</p>
                    </div>
                </div>

                <!-- Coupon Card 5 -->
                <div class="coupon-card">
                    <div class="coupon-logo">
                        <img src="resources/views/front/service-coupons/coupons/500off.jpg" alt="ACR Service Coupons" loading="lazy">
                    </div>
                    <div class="coupon-content">
                        <h3 class="coupon-title">FLAT ₹500 OFF on Any Car Service Above ₹5000.</h3>
                        <div class="coupon-description">
                            <div class="coupon-text">Trust our experts at Auto Car Repair (myTVS) to ensure your car’s optimal performance, safety, and durability. We offer a wide range of car repair and maintenance services. Book your car service today! T&C Apply.</div>
                        </div>
                    </div>
                    <div class="coupon-action">
                        <button class="coupon-button v3-action-btn bookNowButton" data-code="Coupon Applied- *ACR500* FLAT ₹500 OFF on Any Car Service Above ₹5000" onclick="openForm()" data-utm="ACR500">
                            <span class="button-text">GET COUPON</span>
                            <span class="code-text">ACR500</span>
                        </button>
                        <p class="expiry-text">Expires 3 months left</p>
                    </div>
                </div>

                <!-- Coupon Card 6 -->
                <div class="coupon-card">
                    <div class="coupon-logo">
                        <img src="resources/views/front/service-coupons/coupons/pickndrop.jpg" alt="ACR Service Coupons" loading="lazy">
                    </div>
                    <div class="coupon-content">
                        <h3 class="coupon-title">FREE Pick & Drop on Any Service.</h3>
                        <div class="coupon-description">
                            <div class="coupon-text">Auto Car Repair (myTVS) is committed to providing complete customer satisfaction. We offer free pickup and drop service for your car to offer you a convenient and hassle-free experience. Book your car service today! T&C Apply.</div>
                        </div>
                    </div>
                    <div class="coupon-action">
                        <button class="coupon-button v3-action-btn bookNowButton" data-code="FREERIDE" onclick="openForm()" data-utm="Coupon Applied- *FREERIDE* FREE Pick & Drop on Any Service.">
                            <span class="button-text">GET COUPON</span>
                            <span class="code-text">FREERIDE</span>
                        </button>
                        <p class="expiry-text">Expires 2 Weeks left</p>
                    </div>
                </div>

                <!-- Coupon Card 7 -->
                <div class="coupon-card">
                    <div class="coupon-logo">
                        <img src="resources/views/front/service-coupons/coupons/refer.jpg" alt="ACR Service Coupons" loading="lazy">
                    </div>
                    <div class="coupon-content">
                        <h3 class="coupon-title">Refer & Earn ₹500 Discount.</h3>
                        <div class="coupon-description">
                            <div class="coupon-text">Get your car serviced by our expert technicians along with an opportunity to save on your service bill. Refer Auto Car Repair (myTVS) to a friend or family member and get Rs. 500 off. Book your car service today! T&C Apply.</div>
                        </div>
                    </div>
                    <div class="coupon-action">
                        <button class="coupon-button v3-action-btn bookNowButton" data-code="REFER500" onclick="openForm()" data-utm="Coupon Applied- *REFER500* Refer & Earn ₹500 Discount.">
                            <span class="button-text">GET COUPON</span>
                            <span class="code-text">REFER500</span>
                        </button>
                        <p class="expiry-text">Expires 1 Year left</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        // Function to open popup with unfolding animation
        function openForm(offerTitle) {
            document.getElementById('offerNameInput').value = offerTitle;
            document.getElementById('offerNameDisplay').value = offerTitle; // Set the offer name in the display field
            document.getElementById('bookNowPopup').classList.add('active');
            document.getElementById('bookNowPopup').classList.add('one'); // Add the animation class
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-active');

            // Add animation to the form container
            const popupContainer = document.getElementById('bookNowPopup').querySelector('.popup-form-container');
            popupContainer.style.transform = 'scale(0)';
            setTimeout(() => {
                popupContainer.style.animation = 'zoomIn 0.5s 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards';
            }, 100);
        }

        // Function to close popup with unfolding animation
        function closeForm() {
            const bookNowPopup = document.getElementById('bookNowPopup');
            bookNowPopup.classList.add('out');
            document.body.classList.remove('modal-active');

            // Add animation to the form container
            const popupContainer = bookNowPopup.querySelector('.popup-form-container');
            popupContainer.style.animation = 'zoomOut 0.5s cubic-bezier(0.165, 0.84, 0.44, 1) forwards';

            // Wait for animation to complete before hiding
            setTimeout(() => {
                bookNowPopup.classList.remove('active', 'one', 'out');
                document.body.style.overflow = '';
                resetForm();
            }, 1300);
        }

        // Close popup when clicking outside
        document.getElementById('bookNowPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeForm();
            }
        });

        // Close popup when clicking close button
        document.getElementById('closePopupBtn').addEventListener('click', closeForm);

        // Reset form function
        function resetForm() {
            document.getElementById('bookNowForm').reset();
            document.querySelectorAll('.error-message').forEach(el => {
                el.style.display = 'none';
            });
        }

        // Form validation
        function validateForm() {
            let isValid = true;

            // Validate name
            const name = document.getElementById('name').value.trim();
            if (name === '') {
                document.getElementById('nameError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('nameError').style.display = 'none';
            }

            // Validate phone
            const phone = document.getElementById('phone').value.trim();
            const phoneRegex = /^[0-9]{10}$/;
            if (!phoneRegex.test(phone)) {
                document.getElementById('phoneError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('phoneError').style.display = 'none';
            }

            // Validate email if provided
            const email = document.getElementById('email').value.trim();
            if (email !== '') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    document.getElementById('emailError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('emailError').style.display = 'none';
                }
            }

            // Validate location
            const location = document.getElementById('location').value.trim();
            if (location === '' || location === 'Select Location') {
                document.getElementById('locationError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('locationError').style.display = 'none';
            }

            return isValid;
        }

        // Form submission
        document.getElementById('bookNowForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (validateForm()) {
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';

                // Simulate form submission (replace with actual AJAX call)
                setTimeout(() => {
                    // Here you would typically make an AJAX call to your server
                    console.log('Form submitted:', {
                        offer: document.getElementById('offerNameInput').value,
                        name: document.getElementById('name').value,
                        phone: document.getElementById('phone').value,
                        email: document.getElementById('email').value,
                        location: document.getElementById('location').value,
                        message: document.getElementById('message').value
                    });

                    // Show success message
                    alert('Thank you for your booking request! We will contact you shortly.');

                    // Reset form and close popup
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Booking Request';
                    closeForm();
                }, 1500);
            }
        });

        // Add event listeners to all Book Now buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.bookNowButton')) {
                e.preventDefault();

                // Get the offer title from the button
                let offerTitle = e.target.closest('.bookNowButton').getAttribute('data-utm');
                openForm(offerTitle);
            }
        });

        // Add event listener for WhatsApp buttons (if needed)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.whatsapp-btn')) {
                e.preventDefault();
                const url = e.target.closest('.whatsapp-btn').href;
                window.open(url, '_blank');
            }
        });

        // Phone number input validation
        document.addEventListener('DOMContentLoaded', function () {
            var numberInput = document.getElementById("phone");
            numberInput.addEventListener('input', function () {
                var inputValue = numberInput.value.replace(/\D/g, '');
                var slicedValue = inputValue.slice(0, 10);
                numberInput.value = slicedValue;
            });
        });

        // Function to copy to clipboard
        function copyToClipboardModern(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .catch(err => {
                        console.error('Could not copy text: ', err);
                        copyToClipboard(text);
                    });
            } else {
                copyToClipboard(text);
            }
        }

        function copyToClipboard(text) {
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
        }

        // Function to show copied notification
        function showCopiedNotification(code) {
            let notification = document.querySelector('.copied-notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.className = 'copied-notification';
                document.body.appendChild(notification);
            }
            notification.textContent = `Coupon code "${code}" copied to clipboard!`;
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Add event listeners to all coupon buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.coupon-button').forEach(button => {
                button.addEventListener('click', function() {
                    const code = this.getAttribute('data-code');
                    copyToClipboardModern(code);
                    showCopiedNotification(code);
                });
            });
        });
    </script>
@endsection
