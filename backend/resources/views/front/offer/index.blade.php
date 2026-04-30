@extends('front.layout.main')
@section('content')
<!--  <button class="show-more-btn">Show More <i class="fas fa-chevron-right"></i></button> -->
<section class="page-header">
    <div class="page-header__bg"></div>
    <div class="container">
        <h1 class="page-header__title bw-split-in-right">Exclusive Offer</h1>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{url('/')}}"><i class="flaticon-home"></i>Home</a></li>
            <li><span>Offer</span></li>
        </ul>
    </div>
</section>
<style>
    /* Featured Carousel */
    .featured-carousel {
        margin-bottom: 30px;
    }

    .carousel-container {
        position: relative;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .carousel-slides {
        position: relative;
        width: 100%;
    }

    .carousel-slide {
        opacity: 0;
        transition: opacity 0.5s ease, transform 0.5s ease;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }

    .carousel-slide.active {
        opacity: 1;
        position: relative;
    }

    /* Progress Bar and Navigation - Enhanced */
    .testimonials-one__carousel-nav {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
    }

    .progress-container {
        position: relative;
        height: 30px;
        background-color: #ffffff;
        width: 100%;
        margin: 0 10px;
        border: 1px solid #ddd;
        overflow: hidden;
    }

    .progress-bar {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        background-color: #005EFF;
        width: 0%;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .progress-percentage {
        color: black;
        font-weight: bold;
        font-size: 12px;
        z-index: 2;
        width: 100%;
        text-align: center;
        position: absolute;
    }

    /* Offer Cards */
    .featured-offer-card {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    @media (min-width: 768px) {
        .featured-offer-card {
            flex-direction: row;
            height: auto;
        }
    }

    .featured-image-container {
       
        height: auto;
        
        background: #f8f9fa;
        overflow: hidden;
    }

    @media (min-width: 768px) {
        .featured-image-container {
            width: 40%;
            height: auto;
            
        }
    }

    .featured-image {
     
        width: 100%;
        height: auto;
        transition: transform 0.5s ease;
    }

    .featured-offer-card:hover .featured-image {
        transform: scale(1.05);
    }

    .exclusive-tag {
        position: absolute;
        top: 10px;
        right: 0;
        background-color: #005EFF;
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 5px 10px;
        z-index: 1;
        box-shadow: -2px 2px 5px rgba(0, 0, 0, 0.2);
        clip-path: polygon(10% 0%, 100% 0%, 100% 100%, 10% 100%, 0% 50%);
        padding-left: 15px;
    }

    .featured-content {
        padding: 20px;
        background-color: white;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    @media (min-width: 768px) {
        .featured-content {
            width: 60%;
            padding: 25px;
        }
    }

    .featured-title {
        font-size: 26px;
        font-weight: bold;
        margin-bottom: 15px;
        color: black;
        text-align: left;
        text-transform: uppercase;
        text-decoration: underline;
        text-underline-offset: 5px;
    }

    .featured-description {
        font-size: 14px;
        color: #666;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .feature-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .feature-item i {
        color: #22c55e;
        margin-right: 10px;
        font-size: 14px;
        flex-shrink: 0;
        margin-top: 3px;
    }

    .show-more-btn {
        background: none;
        border: none;
        color: #005EFF;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        margin-top: 5px;
        margin-bottom: 15px;
        align-self: flex-start;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }

    .show-more-btn:hover {
        color: #ff3333;
        transform: translateX(5px);
    }

    .show-more-btn i {
        margin-left: 5px;
        transition: transform 0.3s ease;
    }

    .hidden-features {
        display: none;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: auto;
    }

    .action-btn {
        flex: 1;
        padding: 10px 0;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .action-btn i {
        font-size: 14px;
    }

    .phone-btn {
        background-color: #f8f9fa;
        color: #333;
        border: 1px solid #dee2e6;
    }

    .phone-btn:hover {
        background-color: #e9ecef;
    }

    .book-btn {
        background-color: #005EFF;
        color: white;
    }

    .book-btn:hover {
        background-color: #ff3333;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(255, 77, 77, 0.3);
    }

    .chat-btn {
        background-color: #45a413;
        color: #333;
    }

    .chat-btn:hover {
        background-color: #dee2e6;
    }

    /* Offers Section Header */
    .offers-section-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 20px;
    }

    @media (min-width: 768px) {
        .offers-section-header {
            flex-direction: row;
            justify-content: space-between;
        }
    }

    .section-subtitle {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
    }

    @media (min-width: 768px) {
        .section-subtitle {
            margin-bottom: 0;
        }
    }

    /* Offers Grid */
    .offers-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }

    @media (min-width: 576px) {
        .offers-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 992px) {
        .offers-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .offer-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        cursor: pointer;
    }

    .offer-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .card-image-container {
        position: relative;
        height: 100%;
        overflow: hidden;
        background: #f8f9fa;
    }

    .card-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.5s ease;
    }

    .offer-card:hover .card-image {
        transform: scale(1.1);
    }

    .card-badge {
        position: absolute;
        top: 8px;
        right: 0;
        background-color: #005EFF;
        color: white;
        font-size: 10px;
        font-weight: bold;
        padding: 4px 8px;
        clip-path: polygon(10% 0%, 100% 0%, 100% 100%, 10% 100%, 0% 50%);
        padding-left: 12px;
        z-index: 1;
    }

    .card-content {
        padding: 15px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 12px;
        color: #333;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        padding-bottom: 5px;
        background: linear-gradient(90deg, #0092FF 10.94%, #005EFF 75.02%);
        background-size: 0% 2px;
        background-repeat: no-repeat;
        background-position: left bottom;
    } 
    
    .offer-card:hover .card-title {
        color: #005EFF;
        transform: translateY(-2px);
        background-image: linear-gradient(90deg, #0092FF 10.94%, #005EFF 75.02%);
        background-size: 100% 2px;
    }
    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .slide-in-right {
        animation: slideInRight 0.5s ease forwards;
    }

    .slide-in-left {
        animation: slideInLeft 0.5s ease forwards;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(1);
        }
    }

    .pulse {
        animation: pulse 2s infinite;
    }

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
        color:white;
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
</style>


<section class="portfolio-one" id="offers-slider-section">
    <div class="container">
        <div class="sec-title text-center">
            <h6 class="sec-title__tagline bw-split-in-right">
                Our Offer<span class="sec-title__tagline__border"></span>
            </h6>
            <h3 class="sec-title__title bw-split-in-left">
            Quality Servicing, <span>Unbeatable Offers!</span> 
            </h3>
        </div>

        <!-- Featured Offer Carousel -->
        <div class="featured-carousel">
            <div class="carousel-container">
                <div class="carousel-slides" id="featured-carousel-slides">
                    <!-- Slides will be inserted here by JavaScript -->
                </div>
                
                <div class="testimonials-one__carousel-nav">
                    <a href="#" class="testimonials-one__prev portfolio-one__prev"><i class="fi fi-rr-arrow-up-right"></i></a>
                    <div class="progress-container">
                        <div class="progress-bar" id="carousel-progress">
                            <span class="progress-percentage">0%</span>
                        </div>
                    </div>
                    <a href="#" class="testimonials-one__next portfolio-one__next"><i class="fi fi-rr-arrow-up-right"></i></a>
                </div>
            </div>
        </div>
        
        <!-- All Offers Grid -->
        <div class="offers-section-header">
            <h2 class="section-subtitle">All Available Offers</h2>
        </div>
        
        <div class="offers-grid" id="offers-grid">
            <!-- Offer cards will be inserted here by JavaScript -->
        </div>
        <style>
          @media only screen and (max-width: 767px) {
              .offers-section-header,
              #offers-grid {
                  display: none;
              }
          }
          </style>
    </div>
</section>

<!-- Separate Contact Buttons Template for Carousel -->
<template id="carousel-contact-buttons-template">
    <div class="action-buttons">
        <a href="tel:9870400861" class="action-btn phone-btn">
        <i class="fa-solid fa-phone"></i>
            <span>9870400861</span>
        </a>
        <a href="#" class="action-btn book-btn pulse carousel-book-btn">
            <i class="fas fa-calendar"></i>
            <span>Book Now</span>
        </a>
        <a href="https://wa.me/9870400861" class="action-btn chat-btn">
            <i class="fas fa-comment"></i>
            <span>Chat</span>
        </a>
    </div>
</template>

<!-- Separate Contact Buttons Template for Grid -->
<template id="grid-contact-buttons-template">
    <div class="action-buttons">
        <a href="tel:9870400861" class="action-btn phone-btn">
        <i class="fa-solid fa-phone"></i>
            <span>9870400861</span>
        </a>
        <a href="#" class="action-btn book-btn pulse grid-book-btn">
            <i class="fas fa-calendar"></i>
            <span>Book Now</span>
        </a>
        <a href="https://wa.me/9870400861" class="action-btn chat-btn">
            <i class="fas fa-comment"></i>
            <span>Chat</span>
        </a>
    </div>
</template>

<!-- Book Now Popup Form -->
<div class="popup-overlay" id="bookNowPopup">
    <div class="popup-form-container">
        <div class="popup-form-header">
            <h3 class="popup-form-title">Book Service Appointment</h3>
            <button class="popup-close-btn" id="closePopupBtn">&times;</button>
        </div>
        <div class="popup-form-body">
<form id="bookNowForm"  method="POST" 
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
 <!-- HONEYPOT FIELDS (hidden from users, visible to bots) -->
        <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
            <input type="url" name="url" tabindex="-1" autocomplete="off">
        </div> 
    <!-- Offer Name -->
    <input type="hidden" id="offerNameInput" name="offer_name">
    <div class="form-group">
        <input type="text" class="form-control" id="offerNameDisplay" placeholder="Offer Name" readonly required>
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

<script>
   // Global contact information (used by all offers)
   const globalContactInfo = {
        phone: "9870400861",
        bookNow: 'Book Now',
        chat: 'Chat',
        whatsapp: "https://wa.me/9870400861?text=Hi%2C%20I%20am%20interested%20in%20your%20offer.%20Please%20share%20more%20details%3A%20https%3A%2F%2Fautocarrepair.in%2Foffers"
    };


const offers = [

{
    title: "90 Minute Express Service – Fast & Reliable",
    description: "Get your car serviced in just 90 minutes including engine oil change, top wash, and oil filter replacement.",
    image: "resources/views/front/offer/ap-2026/1.jpeg",
    features: [
        "90 Minute Guaranteed Service",
        "Engine Oil Change",
        "Top Wash Included",
        "Oil Filter Replacement",
        "Quick Multi-Point Inspection"
    ]
},

{
    title: "Accidental Car Repair Center – Delhi NCR",
    description: "Complete accident repair services with denting, painting and genuine parts.",
    image: "resources/views/front/offer/ap-2026/2.jpeg",
    features: [
        "Dent & Scratch Removal",
        "Accidental Repair",
        "Paint Touch-up",
        "Genuine Parts Used",
        "Door & Panel Repairs"
    ]
},

{
    title: "Summer Car AC Service Offer",
    description: "Get complete AC checkup & service starting at ₹4,999 with gas top-up included.",
    image: "resources/views/front/offer/ap-2026/3.jpeg",
    features: [
        "Complete AC Inspection",
        "Gas Top-Up Included",
        "Cooling Check",
        "Leak Detection",
        "Summer Ready Package"
    ]
},

{
    title: "Car AC Service – ₹1,499 Offer",
    description: "Special limited time AC service offer to ensure cooling performance.",
    image: "resources/views/front/offer/ap-2026/4.jpeg",
    features: [
        "AC Cooling Check",
        "Basic Service",
        "Filter Cleaning",
        "Quick Service",
        "Affordable Price"
    ]
},

{
    title: "Premium Car Service – ₹9,999",
    description: "Complete premium car service with full inspection and detailing.",
    image: "resources/views/front/offer/ap-2026/5.jpeg",
    features: [
        "Full Car Inspection",
        "Engine Check",
        "Interior Cleaning",
        "Exterior Wash",
        "Premium Package"
    ]
},

{
    title: "Full Body Paint – Premium Finish",
    description: "Restore your car’s shine with full body paint starting at ₹24,999.",
    image: "resources/views/front/offer/ap-2026/6.jpeg",
    features: [
        "Full Body Paint",
        "20% Discount",
        "Free Pickup & Drop",
        "Rubbing & Polishing",
        "Showroom Finish"
    ]
},

{
    title: "Premium PPF Coating – ₹49,999",
    description: "Protect your car with premium paint protection film coating.",
    image: "resources/views/front/offer/ap-2026/7.jpeg",
    features: [
        "Paint Protection Film",
        "Scratch Resistance",
        "High Gloss Finish",
        "Long-Term Protection",
        "Premium Quality"
    ]
}

];

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize variables
        let activeIndex = 0;
        let animationDirection = 'right';
        
        const featuredCarousel = document.getElementById('featured-carousel-slides');
        const progressBar = document.getElementById('carousel-progress');
        const progressPercentage = document.querySelector('.progress-percentage');
        const offersGrid = document.getElementById('offers-grid');
        const sliderSection = document.getElementById('offers-slider-section');
        const carouselContactButtonsTemplate = document.getElementById('carousel-contact-buttons-template');
        const gridContactButtonsTemplate = document.getElementById('grid-contact-buttons-template');
        
        // Book Now Popup Elements
        const bookNowPopup = document.getElementById('bookNowPopup');
        const closePopupBtn = document.getElementById('closePopupBtn');
        const bookNowForm = document.getElementById('bookNowForm');
        const offerNameInput = document.getElementById('offerNameInput');
        
        // Create featured carousel slides
        function createCarouselSlides() {
            featuredCarousel.innerHTML = '';
            
            offers.forEach((offer, index) => {
                // Create slide with unique carousel ID
                const slide = document.createElement('div');
                slide.className = `carousel-slide ${index === activeIndex ? 'active' : ''}`;
                slide.setAttribute('data-carousel-offer-index', index);
                slide.setAttribute('data-carousel-offer-title', offer.title);
                slide.innerHTML = createFeaturedCardHTML(offer, index);
                featuredCarousel.appendChild(slide);
            });
            
            // Add animation class based on direction
            const activeSlide = featuredCarousel.querySelector('.carousel-slide.active');
            if (activeSlide) {
                activeSlide.classList.add(animationDirection === 'right' ? 'slide-in-right' : 'slide-in-left');
            }
            
            // Update progress bar
            updateProgressBar();
            
            // Add event listeners for show more buttons
            document.querySelectorAll('.show-more-btn').forEach(btn => {
                btn.addEventListener('click', toggleFeatures);
            });
        }
        
        // Toggle features visibility
        function toggleFeatures() {
            const hiddenFeatures = this.nextElementSibling;
            
            if (hiddenFeatures.style.display === 'none' || !hiddenFeatures.style.display) {
                hiddenFeatures.style.display = 'block';
                this.innerHTML = 'Show Less <i class="fas fa-chevron-up"></i>';
            } else {
                hiddenFeatures.style.display = 'none';
                this.innerHTML = 'Show More <i class="fas fa-chevron-right"></i>';
            }
        }
        
        // Update progress bar with percentage
        function updateProgressBar() {
            const progress = ((activeIndex + 1) / offers.length) * 100;
            progressBar.style.width = `${progress}%`;
            progressPercentage.textContent = `${Math.round(progress)}%`;
        }
        
        // Create offer cards for the grid
        function createOffersGrid() {
            offersGrid.innerHTML = '';
            
            offers.forEach((offer, index) => {
                const card = document.createElement('div');
                card.className = 'offer-card visible';
                card.dataset.index = index;
                card.setAttribute('data-grid-offer-index', index);
                card.setAttribute('data-grid-offer-title', offer.title);
                card.innerHTML = createOfferCardHTML(offer, index);
                card.style.animation = `fadeIn 0.5s ease forwards ${index * 0.05}s`;
                
                // Add click event to show this offer in the slider
                card.addEventListener('click', function() {
                    const offerIndex = parseInt(this.dataset.index);
                    goToSlide(offerIndex);
                    
                    // Scroll to slider section
                    sliderSection.scrollIntoView({ behavior: 'smooth' });
                });
                
                offersGrid.appendChild(card);
            });
        }
        
        // Create HTML for featured offer card with unique carousel ID
        function createFeaturedCardHTML(offer, index) {
            // Split features into visible and hidden
            const visibleFeatures = offer.features.slice(0, 3);
            const hiddenFeatures = offer.features.slice(3);
            
            return `
                <div class="featured-offer-card" id="carousel-offer-${index}">
                    <div class="featured-image-container">
                        <img src="${offer.image}" alt="${offer.title}" class="featured-image">
                        <div class="exclusive-tag">EXCLUSIVE OFFER</div>
                    </div>
                    <div class="featured-content">
                        <h3 class="featured-title" id="carousel-title-${index}">${offer.title}</h3>
                        <p class="featured-description">${offer.description}</p>
                        ${offer.features.length > 0 ? `
                            <div class="visible-features">
                                ${visibleFeatures.map((feature, i) => `
                                    <div class="feature-item" style="animation-delay: ${i * 0.1}s;">
                                        <i class="fas fa-check-circle"></i>
                                        <span>${feature}</span>
                                    </div>
                                `).join('')}
                            </div>
                           
                            
                            <div class="hidden-features">
                                ${hiddenFeatures.map((feature, i) => `
                                    <div class="feature-item" style="animation-delay: ${i * 0.1}s;">
                                        <i class="fas fa-check-circle"></i>
                                        <span>${feature}</span>
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                        ${carouselContactButtonsTemplate.innerHTML}
                    </div>
                </div>
            `;
        }
        
        // Create HTML for an offer card with unique grid ID
        function createOfferCardHTML(offer, index) {
            return `
                <div class="card-image-container">
                    <img src="${offer.image}" alt="${offer.title}" class="card-image">
                    <div class="card-badge">EXCLUSIVE OFFER</div>
                </div>
                <div class="card-content" id="grid-offer-${index}">
                    <h3 class="card-title" id="grid-title-${index}">${offer.title}</h3>
                    ${gridContactButtonsTemplate.innerHTML}
                </div>
            `;
        }
        
        // Go to a specific slide
        function goToSlide(index) {
            animationDirection = index > activeIndex ? 'right' : 'left';
            activeIndex = index;
            createCarouselSlides();
        }
        
        // Previous slide
        function prevSlide() {
            animationDirection = 'left';
            activeIndex = activeIndex === 0 ? offers.length - 1 : activeIndex - 1;
            createCarouselSlides();
        }
        
        // Next slide
        function nextSlide() {
            animationDirection = 'right';
            activeIndex = activeIndex === offers.length - 1 ? 0 : activeIndex + 1;
            createCarouselSlides();
        }
        
        // Function to open popup with unfolding animation
        function openPopup(offerTitle) {
            offerNameInput.value = offerTitle;
            document.getElementById('offerNameDisplay').value = offerTitle;
            bookNowPopup.classList.add('active');
            bookNowPopup.classList.add('one');
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-active');

            // Add animation to the form container
            const popupContainer = bookNowPopup.querySelector('.popup-form-container');
            popupContainer.style.transform = 'scale(0)';
            setTimeout(() => {
                popupContainer.style.animation = 'zoomIn 0.5s 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards';
            }, 100);
        }
        
        // Function to close popup with unfolding animation
        function closePopup() {
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
        bookNowPopup.addEventListener('click', function(e) {
            if (e.target === bookNowPopup) {
                closePopup();
            }
        });
        
        // Close popup when clicking close button
        closePopupBtn.addEventListener('click', closePopup);
        
        // Reset form function
        function resetForm() {
            bookNowForm.reset();
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
            if (location === '') {
                document.getElementById('locationError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('locationError').style.display = 'none';
            }
            
            return isValid;
        }
        
        // SEPARATE EVENT LISTENERS FOR CAROUSEL AND GRID BOOK NOW BUTTONS
        
        // Carousel Book Now Button Event Listener
        document.addEventListener('click', function(e) {
            if (e.target.closest('.carousel-book-btn')) {
                e.preventDefault();
                
                // Get the currently active carousel slide
                const activeSlide = document.querySelector('.carousel-slide.active');
                let offerTitle = '';
                
                if (activeSlide) {
                    // Get title from data attribute or from the title element
                    offerTitle = activeSlide.getAttribute('data-carousel-offer-title');
                    
                    if (!offerTitle) {
                        // Fallback: get from the title element
                        const titleElement = activeSlide.querySelector('.featured-title');
                        if (titleElement) {
                            offerTitle = titleElement.textContent.trim();
                        }
                    }
                    
                    if (!offerTitle) {
                        // Final fallback: use current active index
                        offerTitle = offers[activeIndex] ? offers[activeIndex].title : 'Car Service Offer';
                    }
                }
                
                console.log('Carousel Book Now clicked - Offer Title:', offerTitle);
                openPopup(offerTitle);
            }
        });
        
        // Grid Book Now Button Event Listener
        document.addEventListener('click', function(e) {
            if (e.target.closest('.grid-book-btn')) {
                e.preventDefault();
                
                // Get the grid card that contains this button
                const gridCard = e.target.closest('.offer-card');
                let offerTitle = '';
                
                if (gridCard) {
                    // Get title from data attribute or from the title element
                    offerTitle = gridCard.getAttribute('data-grid-offer-title');
                    
                    if (!offerTitle) {
                        // Fallback: get from the title element
                        const titleElement = gridCard.querySelector('.card-title');
                        if (titleElement) {
                            offerTitle = titleElement.textContent.trim();
                        }
                    }
                    
                    if (!offerTitle) {
                        // Final fallback: use index from dataset
                        const offerIndex = parseInt(gridCard.dataset.index);
                        offerTitle = offers[offerIndex] ? offers[offerIndex].title : 'Car Service Offer';
                    }
                }
                
                console.log('Grid Book Now clicked - Offer Title:', offerTitle);
                openPopup(offerTitle);
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

        // Initialize carousel and grid
        createCarouselSlides();
        createOffersGrid();
        
        // Add scroll animation for grid items
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.offer-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(15px)';
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            observer.observe(card);
        });
        
        // Add event listeners for carousel navigation
        document.querySelector('.portfolio-one__prev').addEventListener('click', function(e) {
            e.preventDefault();
            prevSlide();
        });

        document.querySelector('.portfolio-one__next').addEventListener('click', function(e) {
            e.preventDefault();
            nextSlide();
        });
        
        // Lazy load images for better performance
        const lazyLoadImages = () => {
            const images = document.querySelectorAll('.featured-image, .card-image');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src || img.src;
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                images.forEach(img => {
                    if (!img.dataset.src) {
                        img.dataset.src = img.src;
                    }
                    imageObserver.observe(img);
                });
            }
        };
        
        // Call lazy load
        lazyLoadImages();
    });
</script>
@endsection