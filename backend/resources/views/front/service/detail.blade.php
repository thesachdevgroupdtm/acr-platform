@extends('front.layout.main')

@section('content')

{{-- ========================================
     SECTION 2: HIDDEN INPUT FIELDS
     ======================================== --}}
<input type="hidden" id="is_service_page" value="1">
<input type="hidden" id="current_service_slug" value="{{ $category->slug ?? '' }}">

{{-- ========================================
     SECTION 3: PAGE HEADER HTML
     ======================================== --}}
<section class="page-header">
  <div class="page-header__bg"></div>
  <div class="container">
    <h1 class="page-header__title bw-split-in-right">{{ $category->title ?? '' }}</h1>
    <ul class="karoons-breadcrumb list-unstyled">
      <li><a href="{{ route('front_/') }}"><i class="flaticon-home"></i>Home</a></li>
      <li><a href="{{ route('front_our-services') }}">Car Service</a></li>
      <li><span>{{ $category->title ?? '' }}</span></li>
    </ul>
  </div>
</section>

{{-- ========================================
     SECTION 4: SELECTED CAR INFO HTML (COMPACT VERSION)
     ======================================== --}}
@if(Session::has('brand_id') && Session::has('model_id') && Session::has('fuel_id'))
<div class="container mt-3">
  <div class="selected-car-info-compact">
    <div class="row align-items-center">
      <div class="col-9">
        <div class="compact-car-details">
          <div class="compact-brand">
            @if(isset($brandquery->image) && $brandquery->image)
            <img src="{{ url('public/uploads/carbrand/'.$brandquery->image) }}" class="compact-car-img" alt="{{ $brandquery->title ?? 'Brand' }}" title="{{ $brandquery->title ?? 'Brand' }}">
            @endif
            <span>{{ $brandquery->title ?? null }}</span>
          </div>
          <div class="compact-separator">/</div>
          <div class="compact-model">
            @if(isset($modelname->image) && $modelname->image)
            <img src="{{ url('public/uploads/carmodel/'.$modelname->image) }}" class="compact-car-img" alt="{{ $modelname->title ?? 'Model' }}" title="{{ $modelname->title ?? 'Model' }}">
            @endif
            <span>{{ $modelname->title ?? null }}</span>
          </div>
          <div class="compact-separator">/</div>
          <div class="compact-fuel">
            @if(isset($fuelname->image) && $fuelname->image)
            <img src="{{ url('public/uploads/fueltype/'.$fuelname->image) }}" class="compact-car-img" alt="{{ $fuelname->title ?? 'Fuel' }}" title="{{ $fuelname->title ?? 'Fuel' }}">
            @endif
            <span>{{ $fuelname->title ?? null }}</span>
          </div>
        </div>
      </div>
      <div class="col-3 text-end">
        <button class="karoons-btn karoons-btn--secondary btn-sm" id="show_search">
          <span>Change Car</span>
        </button>
      </div>
    </div>
  </div>
</div>
@endif

{{-- ========================================
     SECTION 5: SERVICE PACKAGES HTML
     ======================================== --}}
<section class="service-packages-section">
  <div class="container">
    <div class="section-header text-center">
      <h2 class="section-title">Scheduled Packages</h2>
      <div class="section-divider"></div>
      @if(isset($category->brochure) && $category->brochure)
      <div class="mt-4 mb-5">
        <a href="{{ url('public/uploads/service/category/brochures/'.$category->brochure) }}" class="karoons-btn" download>
          <span>Download Brochure</span>
        </a>
      </div>
      @endif
    </div>

    <div class="service-packages-container">
      <div class="parent_service_package">
        @if(isset($detail) && $detail->count())
        @foreach($detail as $record)
        @php($packageDetail = $record)
        <div class="service-package-card">
          <div class="row">
            <div class="col-12 col-md-3">
              <div class="package-image-container">
                @if(isset($packageDetail->note) && $packageDetail->note)
                <div class="package-badge">
                  <span>{{ $packageDetail->note }}</span>
                </div>
                @endif
                @if(isset($packageDetail->image) && $packageDetail->image)
                <img src="{{ url('public/uploads/service/package/'.$packageDetail->image) }}" class="package-image" alt="{{ $packageDetail->title }}" title="{{ $packageDetail->title }}">
                @else
                <img src="{{ asset('front/img/inner-palish-service.png') }}" class="package-image" alt="{{ $packageDetail->title }}" title="{{ $packageDetail->title }}">
                @endif
              </div>
            </div>
            <div class="col-12 col-md-9">
              <div class="package-details">
                <div class="package-header">
                  <h2 class="package-title">{{ $packageDetail->title }}</h2>
                  <div class="package-time">
                    <i class="fa fa-clock"></i>
                    <span>{{ $packageDetail->time_takes_option == "Hour" ? $packageDetail->time_takes . ' Hour(s) Taken' : $packageDetail->time_takes_day . ' Day(s) Taken' }}</span>
                  </div>
                </div>

                <div class="package-info">
                  <ul class="package-info-list">
                    @if(isset($packageDetail->warrenty_info) && $packageDetail->warrenty_info != null)
                    <li class="warranty-info">
                      <i class="fa-solid fa-shield-check"></i>
                      {{ $packageDetail->warrenty_info }}
                    </li>
                    @endif
                    @if(isset($packageDetail->recommended_info) && $packageDetail->recommended_info != null)
                    <li class="recommended-info">
                      <i class="fa-solid fa-thumbs-up"></i>
                      {{ $packageDetail->recommended_info }}
                    </li>
                    @endif
                  </ul>
                </div>

                <div class="package-specifications">
                  <div class="row">
                    @php($specifications = $packageDetail->specifications ?? collect())
                    @if($specifications->count())
                    @foreach($specifications as $skey => $srecord)
                    <div class="col-12 col-sm-6 specification-item s{{ $record->id }} {{ $skey > 4 ? 'd-none' : '' }}">
                      <i class="fa-solid fa-circle-check"></i>
                      <span>{{ $srecord->specification }}</span>
                    </div>
                    @endforeach
                    @endif
                    @if($specifications->count() > 5)
                    <div class="col-12 col-sm-6" id="more{{ $record->id }}">
                      <a href="javascript:void(0)" data-id="{{ $record->id }}" class="view-more-link more">
                        <i class="fa-solid fa-plus"></i>
                        <span>{{ $specifications->count() - 5 }} more specifications</span>
                      </a>
                    </div>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Price section - Add to Cart REMOVED, only Book Now -->
          @if($price_show)
          @php($brand_id = isset($brandquery->id) && $brandquery->id ? $brandquery->id : NULL)
          @php($model_id = isset($modelname->id) && $modelname->id ? $modelname->id : NULL)
          @php($fuel_id = isset($fuelname->id) && $fuelname->id ? $fuelname->id : NULL)
          @php($priceInfo = getServicePrice($brand_id, $model_id, $fuel_id, $record->id))

          <div class="package-footer">
            @if(isset($priceInfo->price) && $priceInfo->price > 0)
            <div class="package-actions">
              @if(Session::has('brand_id') && Session::has('model_id') && Session::has('fuel_id'))
              <!-- Only Book Now button - Add to Cart REMOVED -->
              <button class="karoons-btn karoons-btn--primary book_now_service" data-id="{{ $priceInfo->id }}">
                <i class="fas fa-calendar-alt me-2"></i>
                <span>Book Now</span>
              </button>
              @else
              <button class="karoons-btn " id="show_search">
                <span>Select Your Car</span>
              </button>
              @endif
            </div>
            <div class="package-price">
              <span class="price-label">Price:</span>
              <span class="price-amount">₹ {{ $priceInfo->price }}</span>
            </div>
            @else
            <div class="package-actions">
              <button class="karoons-btn " id="show_search">
                <span>Select Your Car</span>
              </button>
            </div>
            <div class="package-price">
              <span class="price-label">Price:</span>
              <span class="price-amount">N/A</span>
            </div>
            @endif
          </div>
          @else
          <div class="package-footer">
            <div class="package-price">
              <span class="price-label">Price:</span>
              <span class="price-amount">N/A</span>
            </div>
          </div>
          @endif
        </div>
        @endforeach
        @endif

        <div class="explore-more-container text-center">
          <a href="{{ url('our-services') }}" class="karoons-btn ">
            <span>Explore More Services</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ========================================
     BOOK NOW MODAL - OFFER PAGE STYLE (WITH SAME ANIMATIONS)
     ======================================== --}}
<!-- Book Now Modal - New Popup Style -->
<div class="popup-overlay" id="bookNowModal">
    <div class="popup-form-container">
        <div class="popup-form-header">
            <h3 class="popup-form-title">Book Service Appointment</h3>
            <button type="button" class="popup-close-btn" id="closePopupBtn" aria-label="Close">&times;</button>
        </div>
        <div class="popup-form-body">
            <form id="bookNowForm" method="POST" action="{{ route('enquiry.submit') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="book_service_id" name="service_id">
                
                <!-- Selected Car Details - Compact Version -->
                <div class="selected-car-compact mb-3">
                    <div class="compact-details-grid">
                        <div class="compact-detail-item">
                            <div class="compact-icon">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="compact-text">
                                <small>Brand</small>
                                <div id="pre_brand" class="text-truncate">--</div>
                            </div>
                        </div>
                        <div class="compact-detail-item">
                            <div class="compact-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="compact-text">
                                <small>Model</small>
                                <div id="pre_model" class="text-truncate">--</div>
                            </div>
                        </div>
                        <div class="compact-detail-item">
                            <div class="compact-icon">
                                <i class="fas fa-gas-pump"></i>
                            </div>
                            <div class="compact-text">
                                <small>Fuel</small>
                                <div id="pre_fuel" class="text-truncate">--</div>
                            </div>
                        </div>
                        <div class="compact-detail-item">
                            <div class="compact-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="compact-text">
                                <small>Service</small>
                                <div id="pre_service_package" class="text-truncate">--</div>
                            </div>
                        </div>
                    </div>
                </div>

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

                <!-- Email and Preferred Date -->
                <div class="form-row">
                    <div class="form-group half-width">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                        <div class="error-message" id="emailError">Please enter a valid email address</div>
                    </div>
                    <div class="form-group half-width">
                        <input type="date" class="form-control" id="preferred_date" name="preferred_date" placeholder="mm/dd/yyyy">
                    </div>
                </div>

                <!-- Location -->
                <div class="form-group">
                    <select class="form-control" id="location" name="location" required>
                        <option value="" disabled selected>Select Location</option>
                        <option value="ACR Motinagar">Motinagar</option>
                        <option value="ACR Gurgaon">Gurgaon</option>
                        <option value="ACR Noida">Noida</option>
                        <option value="ACR Okhla">Okhla</option>
                    </select>
                    <div class="error-message" id="locationError">Please select your location</div>
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

                <!-- HONEYPOT FIELDS -->
                <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                    <input type="url" name="url" tabindex="-1" autocomplete="off">
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
                <input type="hidden" name="form_type" value="service_booking">

                <!-- Submit Button -->
                <button type="submit" class="karoons-btn w-100" id="submitBtn">
                    <i class="fas fa-calendar-check me-2"></i>
                    <span>Submit Booking Request</span>
                </button>

                <!-- Success Message -->
                <div class="success-message" style="display:none; color:green; margin-top:10px;">
                    Your booking request has been submitted successfully!
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================
     SECTION 8: CAR SELECTION MODAL HTML
     ======================================== --}}
<div class="modal fade" id="carSelectionModal" tabindex="-1" aria-labelledby="carSelectionModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="carSelectionModalLabel">Select Your Car Details</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Progress Bar -->
      <div class="booking-progress-container">
        <div class="booking-progress-bar">
          <div class="progress-step active" data-step="1">
            <div class="step-circle">1</div>
            <span class="step-label">Brand</span>
          </div>
          <div class="progress-step" data-step="2">
            <div class="step-circle">2</div>
            <span class="step-label">Model</span>
          </div>
          <div class="progress-step" data-step="3">
            <div class="step-circle">3</div>
            <span class="step-label">Fuel</span>
          </div>
          <div class="progress-step" data-step="4">
            <div class="step-circle">4</div>
            <span class="step-label">Price</span>
          </div>
        </div>
      </div>

      <div class="modal-body">
        <!-- Step 1: Brand Selection -->
        <div class="booking-step active" id="step-1">
          <div class="step-header">
            <h5 class="step-title">Select Your Car Brand</h5>
            <p class="step-description">Choose your car brand from the list below</p>
          </div>
          <div class="search-container">
            <input type="text" id="search_brand" class="search-input" placeholder="Search brands...">
            <i class="fas fa-search search-icon"></i>
          </div>
          <div class="selection-grid" id="amodal_brands">
            @php($brands = getbrands())
            @if($brands->count())
            @foreach($brands as $brand)
            @if($brand->image)
            <div class="selection-item amodal-brand" data-id="{{$brand->id}}" data-name="{{$brand->title}}">
              <img src="{{ url('public/uploads/carbrand/'.$brand->image) }}" alt="{{$brand->title}}" onerror="this.src='{{ asset('front/img/default-brand.png') }}'">
              <p class="item-name">{{$brand->title}}</p>
            </div>
            @endif
            @endforeach
            @endif
          </div>
          <div class="step-actions">
            <button type="button" class="karoons-btn" data-bs-dismiss="modal">Close</button>
            <button type="button" class="karoons-btn " id="next-to-models" disabled>Next</button>
          </div>
        </div>

        <!-- Step 2: Model Selection -->
        <div class="booking-step" id="step-2">
          <div class="step-header">
            <h5 class="step-title">Select Your Car Model</h5>
            <p class="step-description">Choose your car model from the list below</p>
          </div>
          <div class="search-container">
            <input type="text" id="search_model" class="search-input" placeholder="Search models...">
            <i class="fas fa-search search-icon"></i>
          </div>
          <div class="selection-grid" id="amodal_models">
            <!-- Models will be loaded here -->
          </div>
          <div class="step-actions">
            <button type="button" class="karoons-btn prev-step-btn" data-prev="1">
              <span>Back</span>
            </button>
            <button type="button" class="karoons-btn " id="next-to-fuel" disabled>Next</button>
          </div>
        </div>

        <!-- Step 3: Fuel Type Selection -->
        <div class="booking-step" id="step-3">
          <div class="step-header">
            <h5 class="step-title">Select Fuel Type</h5>
            <p class="step-description">Choose your car's fuel type</p>
          </div>
          <div class="search-container">
            <input type="text" id="search_fuel" class="search-input" placeholder="Search fuel types...">
            <i class="fas fa-search search-icon"></i>
          </div>
          <div class="selection-grid" id="amodal_fuels">
            <!-- Fuel types will be loaded here -->
          </div>
          <div class="step-actions">
            <button type="button" class="karoons-btn prev-step-btn" data-prev="2">
              <span>Back</span>
            </button>
            <button type="button" class="karoons-btn " id="next-to-verify" disabled>Next</button>
          </div>
        </div>

        <!-- Step 4: Verification -->
        <div class="booking-step" id="step-4">

          <div id="search_info">
            <!-- Selected car info will be shown here -->
          </div>

          <div class="step-actions">
            <button type="button" class="karoons-btn prev-step-btn" data-prev="3">
              <span>Back</span>
            </button>
            <button type="button" class="karoons-btn " id="check_price" style="display: none;">Check Price For Free</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  /* ========================================
     OFFER PAGE STYLE ANIMATIONS
     ======================================== */
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
    overflow-y: auto;
    max-height: 90vh;
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

  /* ========================================
     SELECTED CAR INFO COMPACT
     ======================================== */
  .selected-car-info-compact {
    padding: 10px 15px;
    box-shadow: 0px 0px 10px 0.5px rgba(0, 0, 0, 0.13);
    background-color: #f8f9fa;
    border-radius: 8px;
  }

  .compact-car-details {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }

  .compact-brand,
  .compact-model,
  .compact-fuel {
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .compact-car-img {
    width: 30px;
    height: 30px;
    object-fit: contain;
  }

  .compact-separator {
    color: #666;
    font-weight: bold;
  }

  .compact-details-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    padding: 12px;
    background-color: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
  }

  .compact-detail-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .compact-icon {
    width: 30px;
    height: 30px;
    background-color: #005EFF;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 5px;
  }

  .compact-icon i {
    font-size: 14px;
  }

  .compact-text small {
    display: block;
    font-size: 11px;
    color: #666;
    margin-bottom: 2px;
  }

  .compact-text div {
    font-size: 12px;
    font-weight: 600;
    color: #333;
    line-height: 1.2;
  }

  /* ========================================
     SERVICE PACKAGES
     ======================================== */
  .service-packages-section {
    padding: 40px 0;
  }

  .service-package-card {
    background-color: #fff;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    padding: 20px;
    margin-bottom: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .service-package-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
  }

  .package-image-container {
    position: relative;
    margin-bottom: 15px;
  }

  .package-badge {
    position: absolute;
    top: 8px;
    left: 0;
    background-color: #005EFF;
    color: #fff;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    z-index: 1;
  }

  .package-image {
    width: 100%;
    height: auto;
    object-fit: cover;
  }

  .package-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    flex-wrap: wrap;
  }

  .package-title {
    font-size: 18px;
    font-weight: 700;
    color: #222;
    margin: 0;
    flex: 1;
  }

  .package-time {
    display: flex;
    align-items: center;
    color: #666;
    font-size: 13px;
  }

  .package-time i {
    margin-right: 5px;
    color: #005EFF;
  }

  .package-info-list {
    list-style: none;
    padding: 0;
    margin: 0 0 15px 0;
  }

  .package-info-list li {
    display: flex;
    align-items: center;
    margin-bottom: 6px;
    font-size: 13px;
    color: #555;
  }

  .package-info-list li i {
    margin-right: 6px;
    color: #005EFF;
  }

  .package-specifications {
    margin-top: 15px;
  }

  .specification-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 10px;
    font-size: 13px;
  }

  .specification-item i {
    color: #28a745;
    margin-right: 6px;
    margin-top: 2px;
    flex-shrink: 0;
  }

  .view-more-link {
    display: inline-flex;
    align-items: center;
    color: #005EFF;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
  }

  .view-more-link i {
    margin-right: 4px;
  }

  .package-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
  }

  .package-price {
    display: flex;
    align-items: baseline;
  }

  .price-label {
    font-size: 14px;
    color: #666;
    margin-right: 6px;
  }

  .price-amount {
    font-size: 20px;
    font-weight: 700;
    color: #005EFF;
  }

  .package-actions {
    display: flex;
    gap: 8px;
  }

  /* Book Now Button Styling */
  #book_now_service i {
    font-size: 13px;
  }

  .explore-more-container {
    margin-top: 30px;
  }

  /* ========================================
     MODAL DESIGN & SELECTION ITEMS
     ======================================== */

  /* Modal Styling */
  .modal-backdrop {
    background-color: rgba(0, 0, 0, 0.7) !important;
    opacity: 1 !important;
  }

  .modal-content {
    border: none;
    box-shadow: 0 500px 500px 500px rgba(0, 0, 0, 0.2);
  }

  .modal-dialog.modal-lg {
    max-width: 750px;
  }

  .modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 8px 20px;
  }

  .modal-title {
    font-size: 17px;
    font-weight: 600;
    color: white;
    margin: 0;
  }

  .modal-body {
    padding: 8px 20px;
  }

  /* Progress Bar */
  .booking-progress-container {
    padding: 8px 40px !important;
    border-bottom: 1px solid #0b0101ff !important;
  }

  .booking-progress-bar {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    position: relative !important;
  }

  .booking-progress-bar::before {
    content: '';
    position: absolute;
    top: 12px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #e0e0e0;
  }

  .progress-step {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .step-circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background-color: #e0e0e0;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 6px;
    transition: all 0.3s ease;
  }

  .progress-step.active .step-circle {
    background-color: #007bff !important;
    color: white !important;
  }

  .progress-step.completed .step-circle {
    background-color: #28a745 !important;
    color: white !important;
  }

  .step-label {
    font-size: 13px;
    color: #666;
    font-weight: 500;
    white-space: nowrap;
  }

  .progress-step.active .step-label,
  .progress-step.completed .step-label {
    color: #005EFF;
    font-weight: 600;
  }

  /* Step Content */
  .booking-step {
    min-height: auto !important;
    display: none;
  }

  .booking-step.active {
    display: block;
    animation: fadeIn 0.3s ease;
  }

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

  .step-header {
    text-align: center;
    margin-bottom: 8px;
  }

  .step-title {
    font-size: 15px;
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
  }

  .step-description {
    color: #666;
    font-size: 11px;
    margin: 0;
  }

  /* Selection Grid */
  .selection-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) !important;
    gap: 8px !important;
    padding: 8px 0 !important;
    max-height: 280px !important;
    overflow-y: auto !important;
    min-height: 180px !important;
    box-sizing: border-box !important;
  }

  .selection-item {
    background-color: #fff !important;
    border: 2px solid #e0e0e0 !important;
    padding: 4px !important;
    text-align: center !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    min-height: 110px !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    box-sizing: border-box !important;
  }

  .selection-item:hover {
    border-color: #005EFF !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 5px 15px rgba(226, 59, 51, 0.2) !important;
  }

  .selection-item.selected,
  .amodal-brand.selected,
  .amodal-model.selected,
  .amodal-fuel.selected {
    border-color: #28a745 !important;
    background-color: #d4edda !important;
    color: #155724 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 5px 15px rgba(226, 59, 51, 0.3) !important;
  }

  .selection-item.selected::after,
  .amodal-brand.selected::after,
  .amodal-model.selected::after,
  .amodal-fuel.selected::after {
    content: '✓' !important;
    position: absolute !important;
    top: 4px !important;
    right: 4px !important;
    background-color: #28a745 !important;
    color: white !important;
    width: 20px !important;
    height: 20px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 12px !important;
    font-weight: bold !important;
    border-radius: 50% !important;
    border: 2px solid #fff !important;
  }

  .selection-item.selected img,
  .amodal-brand.selected img,
  .amodal-model.selected img,
  .amodal-fuel.selected img {
    border: 2px solid #28a745 !important;
    border-radius: 50% !important;
    padding: 4px !important;
  }

  .selection-item img {
    width: 70px !important;
    height: 70px !important;
    object-fit: contain !important;
    margin-bottom: 2px !important;
    transition: transform 0.3s ease !important;
  }

  .selection-item:hover img {
    transform: scale(1.1) !important;
  }

  .item-name {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #333 !important;
    margin: 0 !important;
    line-height: 1.2 !important;
    text-align: center !important;
  }

  .selection-item.selected .item-name {
    color: #005EFF !important;
  }

  /* Search Container */
  .search-container {
    position: relative !important;
    margin-bottom: 12px !important;
  }

  .search-input {
    width: 100% !important;
    padding: 6px 40px 6px 12px !important;
    border: 5px solid #ddd !important;
    font-size: 13px !important;
    transition: border-color 0.3s ease !important;
  }

  .search-input:focus {
    border-color: #005EFF !important;
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(226, 59, 51, 0.1) !important;
  }

  .search-icon {
    position: absolute !important;
    right: 12px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: #666 !important;
    font-size: 14px !important;
  }

  /* Navigation Buttons */
  .step-actions {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding-top: 15px !important;
    border-top: 1px solid #e0e0e0 !important;
    margin-top: 15px !important;
  }

  .next-step-btn:disabled {
    background: #cccccc;
    cursor: not-allowed;
  }

  .prev-step-btn {
    background-color: #6c757d;
    border-color: #6c757d;
  }

  .prev-step-btn:hover {
    background-color: #5a6268;
    border-color: #545b62;
  }

  /* ========================================
     RESPONSIVE DESIGN
     ======================================== */
  @media (max-width: 767px) {
    .none_mobile {
      display: none !important;
    }

    .none_desk {
      display: block !important;
    }

    .modal-dialog.modal-lg {
      margin: 8px;
      max-width: calc(100% - 16px);
    }

    .booking-progress-container {
      padding: 0 12px 20px !important;
    }

    .selection-grid {
      grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)) !important;
      gap: 10px !important;
    }

    .selection-item {
      min-height: 100px !important;
      padding: 10px 8px !important;
    }

    .selection-item img {
      width: 60px !important;
      height: 60px !important;
    }

    .item-name {
      font-size: 12px !important;
    }

    .service-package-card {
      padding: 12px;
    }

    .package-footer {
      flex-direction: column;
      align-items: stretch;
      gap: 12px;
    }

    .package-price {
      text-align: center;
      margin-bottom: 12px;
    }

    .package-actions {
      flex-direction: column;
      gap: 8px;
      width: 100%;
    }

    .package-actions button {
      width: 100%;
    }

    /* Mobile responsive for compact car details */
    .compact-car-details {
      flex-wrap: wrap;
      gap: 5px;
    }

    .compact-brand,
    .compact-model,
    .compact-fuel {
      font-size: 12px;
    }

    .compact-car-img {
      width: 24px;
      height: 24px;
    }

    .compact-details-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }

    /* Mobile responsive for popup form */
    .popup-form-container {
      width: 95%;
      max-width: 95%;
      max-height: 85vh;
    }
    
    .form-group.half-width {
      flex: 100%;
    }
    
    .form-row {
      gap: 10px;
    }
  }

  @media (min-width: 768px) {
    .none_mobile {
      display: block !important;
    }

    .none_desk {
      display: none !important;
    }
  }

  @media (max-width: 991px) {
    .section-title {
      font-size: 24px;
    }

    .package-header {
      flex-direction: column;
    }

    .package-time {
      margin-top: 8px;
    }
  }
</style>

@endsection

{{-- ========================================
     SECTION 16: JAVASCRIPT - DOCUMENT READY & INITIALIZATION
     ======================================== --}}
@section('javascript')
<script src="{{ asset('front/js/owl.carousel.min.js') }}"></script>
<script>
  // ========================================
  // GLOBAL VARIABLE DECLARATIONS - REQUIRED FOR LINTING
  // ========================================

  var $ = window.$ || window.jQuery
  var toastr = window.toastr

  // ========================================
  // COMPLETE CAR SELECTION MODAL JAVASCRIPT
  // ========================================

  let currentSelections = {
    brand: null,
    model: null,
    fuel: null,
  }

  // ========================================
  // ORIGINAL CODE - Modal Initialization
  // ========================================
  $(document).ready(() => {
    // Clear session data if URL has clear_car_session parameter
    if (getUrlParameter('clear_car_session')) {
      // Remove the parameter from URL without reloading
      clearCarSessionFromUrl();
    }

    // YOUR ORIGINAL CODE - Initialize the car selection modal functionality
    function initializeCarSelectionModal() {
      // YOUR ORIGINAL CODE - Show modal when "Select Your Car" button is clicked
      $(document).on("click", "#show_search", () => {
        $("#carSelectionModal").modal("show")
        resetModalToFirstStep()
      })

      $(document).on("click", ".amodal-brand", function() {
        // Remove previous selections
        $(".amodal-brand").removeClass("selected")
        // Add selection to clicked item with animation
        $(this).addClass("selected")

        // YOUR ORIGINAL CODE - Store selection data
        currentSelections.brand = {
          id: $(this).data("id"),
          name: $(this).data("name"),
        }

        $("#next-to-models").prop("disabled", false).removeClass("disabled")
        $(this).trigger("focus")
      })

      $(document).on("click", ".amodal-model", function() {
        // Remove previous selections
        $(".amodal-model").removeClass("selected")
        // Add selection to clicked item with animation
        $(this).addClass("selected")

        // YOUR ORIGINAL CODE - Store selection data
        currentSelections.model = {
          id: $(this).data("id"),
          name: $(this).data("name"),
        }

        $("#next-to-fuel").prop("disabled", false).removeClass("disabled")
        $(this).trigger("focus")
      })

      $(document).on("click", ".amodal-fuel", function() {
        // Remove previous selections
        $(".amodal-fuel").removeClass("selected")
        // Add selection to clicked item with animation
        $(this).addClass("selected")

        // YOUR ORIGINAL CODE - Store selection data
        currentSelections.fuel = {
          id: $(this).data("id"),
          name: $(this).data("name"),
        }

        $("#next-to-verify").prop("disabled", false).removeClass("disabled")
        $(this).trigger("focus")
      })

      // YOUR ORIGINAL CODE - Next step button handlers
      $("#next-to-models").on("click", () => {
        if (currentSelections.brand) {
          modelFromBrandSearch(currentSelections.brand.id, "")
          goToStep(2)
        }
      })

      $("#next-to-fuel").on("click", () => {
        if (currentSelections.model) {
          fuelFromModelSearch(currentSelections.model.id, "")
          goToStep(3)
        }
      })

      $("#next-to-verify").on("click", () => {
        if (currentSelections.fuel) {
          appointmentnumberModal(currentSelections.fuel.id)
          goToStep(4)
        }
      })

      // YOUR ORIGINAL CODE - Handle back step button clicks
      $(document).on("click", ".prev-step-btn", function() {
        const step = $(this).data("prev")
        goToStep(step)
      })

      // Change car button functionality
      $(document).on("click", "#change_car", function() {
        // Redirect to same page with clear parameter
        window.location.href = window.location.pathname + '?clear_car_session=true';
      });
    }

    // YOUR ORIGINAL CODE - Initialize the modal when document is ready
    initializeCarSelectionModal()
  })

  // Function to get URL parameter
  function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
  }

  // Function to clear car session from URL without reloading
  function clearCarSessionFromUrl() {
    // Remove the clear_car_session parameter from URL
    var url = window.location.href;
    var cleanUrl = url.split('?')[0];
    window.history.replaceState({}, document.title, cleanUrl);

    // Clear session using AJAX (optional)
    // This is a fallback if the PHP session clearing doesn't work
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content");
    $.ajax({
      url: "{{ route('front_model-from-brand-modal') }}", // Use any existing route
      method: "post",
      data: {
        _token: CSRF_TOKEN,
        clear_session: true
      },
      success: (response) => {
        console.log("Session cleared via AJAX");
      }
    });
  }

  // ========================================
  // ORIGINAL CODE - Modal Navigation Functions
  // ========================================


  function resetModalToFirstStep() {
    $(".booking-step").removeClass("active")
    $("#step-1").addClass("active")
    updateProgress(1)
    $("#search_brand").val("")

    setTimeout(() => {
      searchBrand("")
    }, 100)

    // YOUR ORIGINAL CODE - Reset all selections to null
    currentSelections = {
      brand: null,
      model: null,
      fuel: null,
    }

    // YOUR ORIGINAL CODE - Disable all next buttons initially
    $("#next-to-models").prop("disabled", true)
    $("#next-to-fuel").prop("disabled", true)
    $("#next-to-verify").prop("disabled", true)

    $(".selection-item").removeClass("selected")
  }

  // YOUR ORIGINAL CODE - Navigate to specific step in the modal
  function goToStep(stepNumber) {
    $(".booking-step").removeClass("active")
    $("#step-" + stepNumber).addClass("active")
    updateProgress(stepNumber)

    // YOUR ORIGINAL CODE - Clear search inputs and reload data when going back
    if (stepNumber === 1) {
      $("#search_brand").val("")
      setTimeout(() => {
        searchBrand("")
      }, 100)
    } else if (stepNumber === 2) {
      $("#search_model").val("")
      if (currentSelections.brand) {
        setTimeout(() => {
          modelFromBrandSearch(currentSelections.brand.id, "")
        }, 100)
      }
    } else if (stepNumber === 3) {
      $("#search_fuel").val("")
      if (currentSelections.model) {
        setTimeout(() => {
          fuelFromModelSearch(currentSelections.model.id, "")
        }, 100)
      }
    }
  }


  // YOUR ORIGINAL CODE - Update progress bar visual state
  function updateProgress(currentStep) {
    // Reset all progress steps
    $(".progress-step").removeClass("active completed")

    // Mark previous steps as completed and current as active
    for (let i = 1; i <= 4; i++) {
      if (i < currentStep) {
        $(".progress-step[data-step='" + i + "']").addClass("completed")
      } else if (i === currentStep) {
        $(".progress-step[data-step='" + i + "']").addClass("active")
      }
    }
  }

  // ========================================
  // ORIGINAL CODE - AJAX Functions (Enhanced)
  // ========================================

  function modelFromBrandSearch(brand_id, search_model) {
    brand_id = brand_id || ""
    search_model = search_model || ""

    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content")
    $.ajax({
      url: "{{ route('front_model-from-brand-modal') }}",
      method: "post",
      data: {
        _token: CSRF_TOKEN,
        brand_id: brand_id,
        model: search_model
      },
      success: (response) => {
        var resultData = $.parseJSON(response)
        $("#amodal_models").html(resultData.html)

        // YOUR ORIGINAL CODE - Clear any previous model selection
        currentSelections.model = null
        $("#next-to-fuel").prop("disabled", true)

        setTimeout(() => {
          $(".selection-item").removeClass("selected")
        }, 50)
      },
    })
  }

  function fuelFromModelSearch(model_id, search_fuel) {
    model_id = model_id || ""
    search_fuel = search_fuel || ""

    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content")
    $.ajax({
      url: "{{ route('front_search-fuel-from-model') }}",
      method: "post",
      data: {
        _token: CSRF_TOKEN,
        model_id: model_id,
        fuel: search_fuel
      },
      success: (response) => {
        var resultData = $.parseJSON(response)
        $("#amodal_fuels").html(resultData.html)
        $("#back-from-fuel-popup").attr("data-brand_id", resultData.brand_id)

        // YOUR ORIGINAL CODE - Clear any previous fuel selection
        currentSelections.fuel = null
        $("#next-to-verify").prop("disabled", true)

        setTimeout(() => {
          $(".selection-item").removeClass("selected")
        }, 50)
      },
    })
  }

  function searchBrand(search_brand) {
    search_brand = search_brand || ""

    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content")
    $.ajax({
      url: "{{ route('front_search-brand') }}",
      method: "post",
      data: {
        _token: CSRF_TOKEN,
        brand: search_brand
      },
      success: (response) => {
        var resultData = $.parseJSON(response)
        $("#amodal_brands").html(resultData.html)

        if (currentSelections.brand) {
          setTimeout(() => {
            $(".amodal-brand[data-id='" + currentSelections.brand.id + "']").addClass("selected")
            $("#next-to-models").prop("disabled", false)
          }, 100)
        }
      },
    })
  }

  // YOUR ORIGINAL CODE - Load appointment number modal with selected car info
  function appointmentnumberModal(fuel_id) {
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content")
    $.ajax({
      url: "{{ route('front_appoitment-number-modal') }}",
      method: "post",
      data: {
        _token: CSRF_TOKEN,
        fuel_id: fuel_id
      },
      success: (response) => {
        var resultData = $.parseJSON(response)

        if (resultData.result == "success") {
          switch (resultData.type) {
            case "number":
              $("#search_info").html(resultData.html)
              $("#back-from-number-popup").attr("data-model_id", resultData.model_id)
              break
            case "fuel":
              fuelFromModelSearch()
              break
            case "model":
              modelFromBrandSearch()
              break
            default:
              resetModalToFirstStep()
          }
        } else {
          resetModalToFirstStep()
        }

        // YOUR ORIGINAL CODE - Pre-fill mobile number from localStorage if available
        var localstorage_phone = localStorage.getItem("phone")
        $("#appointmentmobile").val(localstorage_phone)
        $("#appointmentresend_text").hide()
        $("#appointmentis_otp_verify").val("1")
        $("#check_price").show()
        $("#appointmentotp").hide()
        $("#appointmentsend_otp").hide()
      },
    })
  }

  // ========================================
  // BOOK NOW FUNCTIONALITY - OFFER PAGE STYLE ANIMATIONS
  // ========================================

  // Function to open popup with unfolding animation (same as offer page)
  function openForm() {
    $('#bookNowModal').addClass('active');
    $('#bookNowModal').addClass('one'); // Add the animation class
    $('body').addClass('modal-active');

    // Add animation to the form container
    const popupContainer = $('#bookNowModal').find('.popup-form-container')[0];
    popupContainer.style.transform = 'scale(0)';
    setTimeout(() => {
      popupContainer.style.animation = 'zoomIn 0.5s 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards';
    }, 100);
  }

  // Function to close popup with unfolding animation (same as offer page)
  function closeForm() {
    const bookNowPopup = $('#bookNowModal')[0];
    bookNowPopup.classList.add('out');
    $('body').removeClass('modal-active');

    // Add animation to the form container
    const popupContainer = bookNowPopup.querySelector('.popup-form-container');
    popupContainer.style.animation = 'zoomOut 0.5s cubic-bezier(0.165, 0.84, 0.44, 1) forwards';

    // Wait for animation to complete before hiding
    setTimeout(() => {
      bookNowPopup.classList.remove('active', 'one', 'out');
      $('body').removeClass('modal-active');
      resetForm();
    }, 1300);
  }

  // Close popup when clicking outside
  $('#bookNowModal').on('click', function(e) {
    if (e.target === this) {
      closeForm();
    }
  });

  // Close popup when clicking close button
  $('#closePopupBtn').on('click', closeForm);

  // Reset form function
  function resetForm() {
    $('#bookNowForm')[0].reset();
    $('.error-message').hide();
  }

  // Form validation
  function validateForm() {
    let isValid = true;

    // Validate name
    const name = $('#name').val().trim();
    if (name === '') {
      $('#nameError').show();
      isValid = false;
    } else {
      $('#nameError').hide();
    }

    // Validate phone
    const phone = $('#phone').val().trim();
    const phoneRegex = /^[0-9]{10}$/;
    if (!phoneRegex.test(phone)) {
      $('#phoneError').show();
      isValid = false;
    } else {
      $('#phoneError').hide();
    }

    // Validate email if provided
    const email = $('#email').val().trim();
    if (email !== '') {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        $('#emailError').show();
        isValid = false;
      } else {
        $('#emailError').hide();
      }
    }

    // Validate location
    const location = $('#location').val();
    if (location === '' || location === null) {
      $('#locationError').show();
      isValid = false;
    } else {
      $('#locationError').hide();
    }

    return isValid;
  }

  // Book Now button click handler
$(document).on('click', '.book_now_service', function () {

    var service_id = $(this).data('id');

    var service_title = $(this)
        .closest('.service-package-card')
        .find('.package-title')
        .text()
        .trim();

    $('#book_service_id').val(service_id);

    openForm();

    $('#pre_brand').text('Loading...');
    $('#pre_model').text('Loading...');
    $('#pre_fuel').text('Loading...');
    
    // correct service name
    $('#pre_service_package').text(service_title || '--');

    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ route('front_book-now') }}",
        type: "POST",
        dataType: "json",
        data: {
            _token: CSRF_TOKEN,
            service_id: service_id
        },

        success: function (response) {

            if (response.success === true) {

                var data = response.data;

                $('#pre_brand').text(data.brand || '--');
                $('#pre_model').text(data.model || '--');
                $('#pre_fuel').text(data.fuel || '--');

                if (data.user_name) $('#name').val(data.user_name);
                if (data.user_email) $('#email').val(data.user_email);
                if (data.user_phone) $('#phone').val(data.user_phone);

            } else {

                $('#pre_brand').text('--');
                $('#pre_model').text('--');
                $('#pre_fuel').text('--');

            }

        },

        error: function () {

            $('#pre_brand').text('N/A');
            $('#pre_model').text('N/A');
            $('#pre_fuel').text('N/A');

        }

    });

});

  // Phone number input validation
  $(document).on('input', '#phone', function() {
      var inputValue = $(this).val().replace(/\D/g, '');
      var slicedValue = inputValue.slice(0, 10);
      $(this).val(slicedValue);
  });

  // Close modal on Escape key
  $(document).on('keydown', function(e) {
      if (e.key === 'Escape' && $('#bookNowModal').hasClass('active')) {
          closeForm();
      }
  });

  // Form submission
  $('#bookNowForm').on('submit', function(e) {
    e.preventDefault();

    if (validateForm()) {
      const submitBtn = $('#submitBtn');
      submitBtn.prop('disabled', true);
      submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i><span>Processing...</span>');

      // Simulate form submission (replace with actual AJAX call)
      setTimeout(() => {
        // Here you would typically make an AJAX call to your server
        console.log('Form submitted:', {
          service_id: $('#book_service_id').val(),
          name: $('#name').val(),
          phone: $('#phone').val(),
          email: $('#email').val(),
          location: $('#location').val(),
          preferred_date: $('#preferred_date').val()
        });

        // Show success message
        $('.success-message').show();

        // Reset form and close popup after delay
        setTimeout(() => {
          submitBtn.prop('disabled', false);
          submitBtn.html('<i class="fas fa-calendar-check me-2"></i><span>Submit Booking Request</span>');
          closeForm();
        }, 2000);
      }, 1500);
    }
  });

  // ========================================
  // YOUR ORIGINAL CODE - Search Functionality
  // ========================================
  $(document).on("keyup", "#search_brand", function() {
    searchBrand($(this).val())
  })

  $(document).on("keyup", "#search_model", function() {
    if (currentSelections.brand) {
      modelFromBrandSearch(currentSelections.brand.id, $(this).val())
    }
  })

  $(document).on("keyup", "#search_fuel", function() {
    if (currentSelections.model) {
      fuelFromModelSearch(currentSelections.model.id, $(this).val())
    }
  })

  // ========================================
  // YOUR ORIGINAL CODE - OTP FUNCTIONS (Keep exactly as they were)
  // ========================================

  function sendOTP() {
    var validateMobNum = /[1-9]{1}[0-9]{9}/
    var mobile = $("#appointmentmobile").val()
    if (validateMobNum.test(mobile) && mobile.length == 10) {
      var CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content")
      $.ajax({
        url: "{{ route('front_send-otp') }}",
        method: "post",
        data: {
          _token: CSRF_TOKEN,
          mobile: mobile
        },
        success: (result) => {
          var resultData = $.parseJSON(result)
          if (resultData.result == "success") {
            $("#appointmentmobile").attr("readonly", "readonly")
            $(".aptotp-section").show()
            $("#appointmentsend_otp").hide()
            apttimer(30)
          } else {
            if (typeof toastr !== "undefined") {
              toastr.error("Something went wrong. Please try again later!")
            } else {
              alert("Something went wrong. Please try again later!")
            }
          }
        },
      })
    } else {
      if (typeof toastr !== "undefined") {
        toastr.error("Please Enter Valid Mobile No.")
      } else {
        alert("Please Enter Valid Mobile No.")
      }
    }
  }

  function verifyOTP() {
    var mobile = $("#appointmentmobile").val()
    var otp = $("#appointmentotp").val()
    var olength = otp.toString().length
    if (Number.parseInt(olength) > 3) {
      var CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content")
      $.ajax({
        url: "{{ route('front_verify-otp') }}",
        method: "post",
        data: {
          _token: CSRF_TOKEN,
          mobile: mobile,
          otp: otp
        },
        success: (result) => {
          var resultData = $.parseJSON(result)
          if (resultData.result == "success") {
            localStorage.setItem("phone", mobile)
            $("#appointmentresend_text").hide()
            $("#appointmentis_otp_verify").val("1")
            $("#check_price").show()
            $("#appointmentmobile").attr("readonly", "readonly")
            $("#appointmentotp").hide()
          } else {
            if (typeof toastr !== "undefined") {
              toastr.error("Please Enter Valid OTP.")
            } else {
              alert("Please Enter Valid OTP.")
            }
          }
        },
      })
    }
  }

  function resendOTP() {
    var validateMobNum = /[1-9]{1}[0-9]{9}/
    var mobile = $("#appointmentmobile").val()
    if (validateMobNum.test(mobile) && mobile.length == 10) {
      var CSRF_TOKEN = $('meta[name="csrf-token"]').attr("content")
      $.ajax({
        url: "{{ route('front_resend-otp') }}",
        method: "post",
        data: {
          _token: CSRF_TOKEN,
          mobile: mobile
        },
        success: (result) => {
          var resultData = $.parseJSON(result)
          if (resultData.result == "success") {
            $(".aptotp-section").show()
            $("#appointmentresend_text").show()
            $("#appointmentotp").val("").show()
            $("#appointmentmobile").attr("readonly", "readonly")
            $("#appointmentresend_otp").hide()
            apttimer(30)
          } else {
            if (typeof toastr !== "undefined") {
              toastr.error("Something went wrong. Please try again later!")
            } else {
              alert("Something went wrong. Please try again later!")
            }
          }
        },
      })
    } else {
      if (typeof toastr !== "undefined") {
        toastr.error("Please Enter Valid Mobile No.")
      } else {
        alert("Please Enter Valid Mobile No.")
      }
    }
  }

  const timerStart = true

  function apttimer(remaining) {
    var m = Math.floor(remaining / 60)
    var s = remaining % 60
    m = m < 10 ? "0" + m : m
    s = s < 10 ? "0" + s : s
    document.getElementById("apttimer").innerHTML = m + ":" + s
    remaining -= 1

    if (remaining >= 0 && timerStart) {
      setTimeout(() => {
        apttimer(remaining)
      }, 1000)
      return
    }

    if (!timerStart) {
      return
    }

    var is_otp_verify = $("#appointmentis_otp_verify").val()
    if (is_otp_verify == "0") {
      $("#appointmentresend_otp").show()
      $("#appointmentmobile").removeAttr("readonly")
      $("#appointmentresend_text").hide()
      $("#appointmentotp").hide()
    }
  }

  // ========================================
  // YOUR ORIGINAL CODE - Event Handlers & Utilities
  // ========================================

  $(document).ready(() => {
   
   // OTP event handlers
    $("#appointmentresend_otp").hide()
    $(".aptotp-section").hide()
  })

  // View more specifications functionality
  $(document).on("click", ".more", function() {
    var id = $(this).data("id")
    $(".s" + id).removeClass("d-none")
    $(this).parent().remove()
  })

  $(document).on("click", "#appointmentsend_otp", () => {
    sendOTP()
  })

  $(document).on("keyup", "#appointmentotp", () => {
    verifyOTP()
  })

  $(document).on("click", "#appointmentresend_otp", () => {
    resendOTP()
  })

  // Check price button handler
  $(document).on("click", "#check_price", () => {
    var is_service_page = $("#is_service_page").val()
    if (is_service_page == 1) {
      var category_slug = $("#current_service_slug").val()
      var csrfToken = $('meta[name="csrf-token"]').attr("content")
      $.ajax({
        url: "{{ route('front_get-current-model') }}",
        type: "POST",
        data: {
          category_slug: category_slug,
          _token: csrfToken
        },
        success: (data) => {
          var resultData = $.parseJSON(data)
          var model_slug = resultData.slug
          var href = "{{url('/')}}" + "/" + category_slug + "/" + model_slug
          location.href = href
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error("AJAX Error: " + textStatus, errorThrown)
        },
      })
    } else {
      window.location.reload()
    }
  })
</script>
@endsection