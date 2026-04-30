@extends('front.layout.main')

@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <style>
        :root {
            --primary-color: #2a76e8;
            --primary-light: #e8f2ff;
            --secondary-color: #ff6b6b;
            --dark-color: #2d3748;
            --light-color: #f8f9fa;
            --border-color: #e2e8f0;
            --success-color: #38a169;
            --gray-medium: #718096;
            --gray-light: #edf2f7;
        }
        
        .checkout-page {
            padding: 40px 0;
            background: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .checkout-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
            padding: 28px;
            margin-bottom: 28px;
            border: 1px solid var(--border-color);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 22px;
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a5568;
        }
        
        .form-control {
            width: 100%;
            height: 50px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0 18px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(42, 139, 242, 0.15);
        }
        
        .otp-section {
            background: var(--primary-light);
            padding: 22px;
            border-radius: 10px;
            margin-top: 18px;
            border-left: 4px solid var(--primary-color);
        }
        
        .date-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
            margin-bottom: 28px;
        }
        
        @media (max-width: 1200px) {
            .date-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .date-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .date-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .date-card {
            background: #fff;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 16px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .date-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-color: var(--primary-color);
        }
        
        .date-card.selected {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
        
        .date-day {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .date-weekday {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .time-slots-container {
            margin-top: 22px;
        }
        
        .time-slot-section {
            margin-bottom: 28px;
        }
        
        .time-slot-header {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .time-slot-title {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            color: var(--dark-color);
        }
        
        .time-slot-badge {
            background: var(--primary-color);
            color: #fff;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-right: 12px;
            font-weight: 600;
        }
        
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        
        @media (max-width: 992px) {
            .time-slots-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .time-slots-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .time-slots-grid {
                grid-template-columns: repeat(1, 1fr);
            }
        }
        
        .time-slot-btn {
            display: block;
            width: 100%;
            padding: 14px 8px;
            background: #fff;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            cursor: pointer;
            color: var(--dark-color);
        }
        
        .time-slot-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .time-slot-btn.selected {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(42, 118, 232, 0.2);
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-top: 22px;
        }
        
        @media (max-width: 576px) {
            .payment-options {
                grid-template-columns: 1fr;
            }
        }
        
        .payment-option {
            background: #fff;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 24px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }
        
        .payment-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .payment-option.selected {
            border-color: var(--primary-color);
            background: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .payment-option img {
            height: 40px;
            margin-bottom: 14px;
        }
        
        .payment-option label {
            display: block;
            font-weight: 600;
            margin: 0;
            cursor: pointer;
            font-size: 16px;
            color: var(--dark-color);
        }
        
        .payment-option .payment-subtext {
            font-size: 13px;
            color: var(--gray-medium);
            margin-top: 6px;
        }
        
        .order-summary-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
            padding: 28px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 16px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s;
            margin-top: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary:hover {
            background: #1a67d2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 139, 242, 0.25);
        }
        
        .btn-otp {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 12px;
            font-size: 15px;
        }
        
        .btn-otp:hover {
            background: #1a67d2;
        }
        
        .saved-addresses {
            margin-top: 22px;
        }
        
        .address-card {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .address-card:hover {
            border-color: var(--primary-color);
        }
        
        .address-card.selected {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }
        
        .address-card label {
            display: flex;
            align-items: center;
            margin: 0;
            cursor: pointer;
            font-size: 15px;
        }
        
        .address-card i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        #resend_text {
            font-size: 14px;
            margin-top: 12px;
            color: #666;
        }
        
        .vehicle-details {
            background: var(--primary-light);
            padding: 18px;
            border-radius: 8px;
            margin-top: 16px;
            border-left: 3px solid var(--primary-color);
        }
        
        .empty-cart-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-medium);
        }
        
        .empty-cart-message i {
            font-size: 56px;
            margin-bottom: 18px;
            color: #cbd5e0;
        }
        
        .empty-cart-message h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: var(--dark-color);
        }
        
        .slot-count {
            font-size: 15px;
            font-weight: 600;
            color: var(--primary-color);
            margin-left: 8px;
        }
    </style>
@endsection

@section('content')
<section class="checkout-page">
    <div class="container">
        <form method="POST" action="{{route('front_create-order')}}" id="checkout-form" enctype="multipart/form-data" data-parsley-validate="">
            @csrf
            <div class="row">
                <div class="col-xl-6 col-lg-6">
                    <!-- Personal Details Section -->
                    <div class="checkout-section">
                        <h2 class="section-title"><i class="fas fa-user"></i> Personal Details</h2>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="name">Full Name *</label>
                                    <input class="form-control" id="name" type="text" name="name" required value="{{ Auth::guard('user')->check() ? Auth::guard('user')->user()->firstname.' '.Auth::guard('user')->user()->lastname : ''}}" placeholder="Your Full Name">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="mobile">Phone Number *</label>
                                    <input class="form-control" id="mobile" type="tel" class="num_only" maxlength="10" required name="mobile" value="{{ Auth::guard('user')->check() ? Auth::guard('user')->user()->phone : ''}}" placeholder="Your Phone Number">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="email">Email Address *</label>
                                    <input class="form-control" id="email" name="email" type="email" required value="{{ Auth::guard('user')->check() ? Auth::guard('user')->user()->email : ''}}" placeholder="Your Email Address">
                                </div>
                            </div>
                            <div class="col-md-12 d-none" id="vehicle">
                                <div class="form-group">
                                    <label class="form-label" for="vehicle_number">Vehicle Number</label>
                                    <input class="form-control" id="vehicle_number" type="text" class="alphanumeric" maxlength="15" name="vehicle_number" value="{{ Auth::guard('user')->check() ? Auth::guard('user')->user()->vehicle_number : ''}}" placeholder="Your Vehicle Number">
                                </div>
                            </div>
                            
                            <div class="col-md-12 otp-section" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label" for="otp">OTP Verification *</label>
                                    <input class="form-control" id="otp" type="text" class="num_only" name="otp" placeholder="Enter OTP">
                                    <div id="resend_text"><b>Resend OTP in <span id="timer"></span> seconds</b></div>
                                </div>
                                <button type="button" id="resend_otp" class="btn-otp">
                                    <i class="fas fa-redo-alt"></i> RESEND OTP
                                </button>
                            </div>
                            
                            <div class="col-md-12">
                                <button type="button" id="send_otp" class="btn-otp">
                                    <i class="fas fa-paper-plane"></i> SEND OTP
                                </button>
                                <input type="hidden" id="is_otp_verify" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Address Section -->
                    <div class="checkout-section">
                        <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Delivery Address</h2>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="address">Address *</label>
                                    <input class="form-control" id="address" type="text" name="address" required placeholder="Your Address" value="{{ isset($addresses) && $addresses->count() ? $addresses->first()->address : '' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="zip">Pincode *</label>
                                    <input class="form-control" id="zip" type="text" class="num_only" maxlength="6" required name="zip" placeholder="Pincode" value="{{ isset($addresses) && $addresses->count() ? $addresses->first()->zip : '' }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="city">City *</label>
                                    <input class="form-control" id="city" type="text" name="city" required placeholder="City" value="{{ isset($addresses) && $addresses->count() ? $addresses->first()->city : '' }}">
                                </div>
                            </div>
                            
                            @if(isset($addresses) && $addresses->count())
                            <div class="col-md-12">
                                <h3 class="section-title" style="font-size: 18px;">Choose From Saved Addresses</h3>
                                <div class="saved-addresses">
                                    @foreach($addresses as $aval)
                                        <div class="address-card">
                                            <input type="radio" name="address_radio" value="{{$aval->id}}" id="address_{{$aval->id}}" class="form-check-input address_radio" {{ $loop->first ? 'checked' : '' }}>
                                            <label for="address_{{$aval->id}}">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span>{{$aval->address}} , {{$aval->zip}}, {{$aval->city}}</span>
                                            </label>
                                            <input type="hidden" id='uaddress{{$aval->id}}' value="{{$aval->address}}">
                                            <input type="hidden" id='uzip{{$aval->id}}' value="{{$aval->zip}}">
                                            <input type="hidden" id='ucity{{$aval->id}}' value="{{$aval->city}}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Service Date & Time Section -->
                    <div class="checkout-section d-none" id="service_slot_section">
                        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Choose Service Date & Time</h2>
                        
                        <div class="date-grid">
                            @php($weekdays = weekOfDays('6'))
                            @if($weekdays)
                                @foreach($weekdays as $week)
                                    <div class="date-card slot-date" data-date="{{date('Y-m-d', strtotime($week))}}">
                                        <div class="date-day">{{date('d M', strtotime($week))}}</div>
                                        <div class="date-weekday">{{date('D', strtotime($week))}}</div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        
                        <div class="time-slots-container">
                            <h4 class="time-slot-header">Pick Time Slot <span class="slot-count" id="total_slots">({{$aslots->count()+$eslots->count()+$mslots->count()}} slots available)</span></h4>
                            <input type="hidden" name="slot_date" value="">
                            <input type="hidden" name="slot_time" value="">
                            
                            <div id="slot_info">
                                @if($mslots->count())
                                    <div class="time-slot-section">
                                        <h4 class="time-slot-title"><span class="time-slot-badge">Morning</span> Morning Slot</h4>
                                        <div class="time-slots-grid">
                                            @foreach($mslots as $slot)
                                                <button type="button" class="time-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if($aslots->count())
                                    <div class="time-slot-section">
                                        <h4 class="time-slot-title"><span class="time-slot-badge">Afternoon</span> Afternoon Slot</h4>
                                        <div class="time-slots-grid">
                                            @foreach($aslots as $slot)
                                                <button type="button" class="time-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if($eslots->count())
                                    <div class="time-slot-section">
                                        <h4 class="time-slot-title"><span class="time-slot-badge">Evening</span> Evening Slot</h4>
                                        <div class="time-slots-grid">
                                            @foreach($eslots as $slot)
                                                <button type="button" class="time-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Selection -->
                    <div class="checkout-section">
                        <h2 class="section-title"><i class="fas fa-credit-card"></i> Payment Selection</h2>
                        <p style="margin-bottom: 20px; color: var(--gray-medium);">Select a payment method and place your order:</p>
                        
                        <div class="payment-options">
                            <div class="payment-option selected" id="online-payment-option">
                                <input class="form-check-input" type="radio" name="payment_type" value="0" id="pay_online" checked>
                                <img src="{{ asset('front/img/cashless-payment.png') }}" alt="Online Payment">
                                <label for="pay_online">Online Payment</label>
                                <div class="payment-subtext">Pay Online</div>
                            </div>
                            <div class="payment-option" id="cash-payment-option">
                                <input class="form-check-input" type="radio" name="payment_type" value="1" id="pay_cash">
                                <img src="{{ asset('front/img/rupee.png') }}" alt="Cash Payment">
                                <label for="pay_cash">Cash Payment</label>
                                <div class="payment-subtext">Pay Cash</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary Section -->
                <div class="col-xl-6 col-lg-6">
                    <div class="order-summary-card">
                        <h2 class="section-title"><i class="fas fa-shopping-bag"></i> Your Order</h2>
                        <div class="card-detial-sec-main"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
<!-- Include SweetAlert for confirmation dialogs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
    console.log("Checkout page loaded");
    
    getCartAjaxHtml();

    // Payment option selection
    const onlineOption = document.getElementById('online-payment-option');
    const cashOption = document.getElementById('cash-payment-option');
    const onlineRadio = document.getElementById('pay_online');
    const cashRadio = document.getElementById('pay_cash');
    
    if (onlineOption && cashOption) {
        onlineOption.addEventListener('click', function() {
            onlineOption.classList.add('selected');
            cashOption.classList.remove('selected');
            onlineRadio.checked = true;
        });
        
        cashOption.addEventListener('click', function() {
            cashOption.classList.add('selected');
            onlineOption.classList.remove('selected');
            cashRadio.checked = true;
        });
    }

    // Form submission handling
    $("#checkout-form").submit(function(e) {
        $('#booking_confirm').addClass('d-none');
        $('#loading_btn').removeClass('d-none');
        
        var slot_time = $('input[name="slot_time"]').val();
        var slot_date = $('input[name="slot_date"]').val();
        var is_service_in_cart = $('input[name="is_service_in_cart"]').val();
        
        if(slot_date == '' && is_service_in_cart == '1'){
            $('#booking_confirm').removeClass('d-none');
            $('#loading_btn').addClass('d-none');
            toastr.error('Please select slot date!');
            return false;
        } else if(slot_time == '' && is_service_in_cart == '1'){
            $('#booking_confirm').removeClass('d-none');
            $('#loading_btn').addClass('d-none');
            toastr.error('Please select slot time!');
            return false;
        } else {
            $('#booking_confirm').addClass('d-none');
            $('#loading_btn').removeClass('d-none');
            return true;
        }
   });

    // Slot selection
    $(document).on('click', '.slot-btn', function(){
        var id = $(this).data('id');
        $('input[name="slot_time"]').val(id);
        $('.slot-btn').removeClass('selected');
        $(this).addClass('selected');
    });

    // Date selection
    $(document).on('click', '.slot-date', function(){
        var date = $(this).data('date');
        $this = $(this);
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url : '{{ route('front_get-available-slot') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, date:date},
            success : function(result){
                var result = $.parseJSON(result);
                $('#slot_info').html(result.html);
                $('input[name="slot_date"]').val(date);
                $('.slot-btn').removeClass('selected');
                $('input[name="slot_time"]').val('');
                $('.slot-date').removeClass('selected');
                $this.addClass('selected');
                $('#total_slots').html("("+result.total_slots+" slots available)");
            }
        });
    });

    // Address selection
    $(document).on('click', '.address_radio', function(){
        var id = $("input[name='address_radio']:checked").val();
        if(id){
            var address = $('#uaddress'+id).val();
            var zip = $('#uzip'+id).val();
            var city = $('#ucity'+id).val();

            $('#address').val(address);
            $('#zip').val(zip);
            $('#city').val(city);
        }
    });

    // Plus button - FIXED for checkout page
    $(document).on('click', '.plus-btn', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var qtyInput = $('#qty'+id);
        var qty = parseInt(qtyInput.val());
        qty = qty + 1;
        qtyInput.val(qty);
        updateCart(id, qty);
    });

    // Minus button - FIXED for checkout page
    $(document).on('click', '.minus-btn', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var qtyInput = $('#qty'+id);
        var qty = parseInt(qtyInput.val());
        
        if(qty > 1){
            qty = qty - 1;
            qtyInput.val(qty);
            updateCart(id, qty);
        } else {
            Swal.fire({
                title: "Are you sure?",
                text: "You want to delete this item from cart!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "Yes",
                cancelButtonText: "{{__('Cancel')}}",
            }).then((result) => {
                if (result.isConfirmed) {
                    removeFromCart(id);
                }
            });
        }
    });

    // Remove button - FIXED for checkout page
    $(document).on('click', '.cart-remove', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            title: "Are you sure?",
            text: "You want to delete this item from cart!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Yes",
            cancelButtonText: "{{__('Cancel')}}",
        }).then((result) => {
            if (result.isConfirmed) {
                removeFromCart(id);
            }
        });
    });

    // OTP functionality
    $('#resend_otp').hide();
    $('.otp-section').hide();
    $('#booking_confirm').show();

    $(document).on('click', '#send_otp', function(){
        var validateMobNum= /[1-9]{1}[0-9]{9}/;
        var mobile = $('#mobile').val();
        if (validateMobNum.test(mobile) && mobile.length == 10) {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url : '{{ route('front_send-otp') }}',
                method : 'post',
                data : {_token: CSRF_TOKEN, mobile:mobile},
                success : function(result){
                    var result = $.parseJSON(result);
                    if(result.result == 'success'){
                        $("#mobile").attr("readonly", "readonly");
                        $('.otp-section').show();
                        $('#send_otp').hide();
                        timer(30);
                    } else {
                        toastr.error('Something went wrong. Please try again later!');
                    }
                }
            });
        }
        else {
            toastr.error('Please Enter Valid Mobile No.');
        }
    });

    $(document).on('keyup', '#otp', function(){
        var mobile = $('#mobile').val();
        var otp = $('#otp').val();
        var olength = otp.toString().length;
        if(parseInt(olength) > 3){
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url : '{{ route('front_verify-otp') }}',
                method : 'post',
                data : {_token: CSRF_TOKEN, mobile:mobile, otp:otp},
                success : function(result){
                    var result = $.parseJSON(result);
                    if(result.result == 'success'){
                        localStorage.setItem("phone", mobile);
                        $('#resend_text').hide();
                        $('#is_otp_verify').val('1');
                        $('#booking_confirm').show();
                        $("#mobile").attr("readonly", "readonly"); 
                        $('#otp').hide();
                    } else {
                        toastr.error('Please Enter Valid OTP.');
                    }
                }
            });
        }
    });

    $(document).on('click', '#resend_otp', function(){
        var validateMobNum= /[1-9]{1}[0-9]{9}/;
        var mobile = $('#mobile').val();
        if (validateMobNum.test(mobile) && mobile.length == 10) {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url : '{{ route('front_resend-otp') }}',
                method : 'post',
                data : {_token: CSRF_TOKEN, mobile:mobile},
                success : function(result){
                    var result = $.parseJSON(result);
                    if(result.result == 'success'){
                        $('.otp-section').show();
                        $('#resend_text').show();
                        $('#otp').val('');
                        $('#otp').show();
                        $("#mobile").attr("readonly", "readonly");
                        $('#resend_otp').hide();
                        timer(30);
                    } else {
                        toastr.error('Something went wrong. Please try again later!');
                    }
                }
            });
        }
        else {
            toastr.error('Please Enter Valid Mobile No.');
        }
    });

    $(document).on('keyup', '#mobile', function(){
        var validateMobNum= /[1-9]{1}[0-9]{9}/;
        var mobile = $('#mobile').val();
        if (validateMobNum.test(mobile) && mobile.length == 10) {
            var verified_mobile = localStorage.getItem("phone");
            if(verified_mobile != mobile){
                $('#booking_confirm').hide();
                $('#send_otp').show();
            } else {
                $('#booking_confirm').show();
                $('#send_otp').hide();
            }
        }
    });
    
    // Update cart function - FIXED
    function updateCart(cart_id, qty){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('front_update-cart') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, cart_id : cart_id, qty : qty},
            beforeSend: function() {
                // Show loading indicator
                $('#qty'+cart_id).closest('.cart-item').css('opacity', '0.7');
            },
            success : function(result){
                getCartAjaxHtml();
            },
            error: function() {
                // Restore opacity on error
                $('#qty'+cart_id).closest('.cart-item').css('opacity', '1');
            }
        });
    }

    function getCartAjaxHtml(){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        var loggedin_user_mobile = "{{ Auth::guard('user')->check() ? Auth::guard('user')->user()->phone : ''}}";
        $.ajax({
            url : '{{ route('front_checkout-ajax-html') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN},
            beforeSend: function() {
                // Show loading indicator
                $('.card-detial-sec-main').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading cart...</p></div>');
            },
            success : function(result){
                try {
                    var result = typeof result === 'string' ? $.parseJSON(result) : result;
                    if(result.status == 'success'){
                        $('.card-detial-sec-main').html(result.html);
                        var phone = localStorage.getItem("phone");
                        if(loggedin_user_mobile == ''){
                            $('#mobile').val(phone);
                        }
                        var is_verify_otp = $('#is_otp_verify').val();
                        if(is_verify_otp == '0'){
                            var mobile = $('#mobile').val();
                            if(phone == mobile) {
                                $('#booking_confirm').show();
                                $('#send_otp').hide();
                            }
                            else {
                                $('#booking_confirm').hide();
                                $('#send_otp').show();
                            }
                        }
                        setTimeout(function(){
                            var is_service_available = $('input[name="is_service_in_cart"]').val();
                            if(is_service_available == '1'){
                                $('#service_slot_section').removeClass('d-none');
                                $('#vehicle').removeClass('d-none');
                            } else {
                                $('#service_slot_section').addClass('d-none');
                                $('#vehicle').addClass('d-none');
                            }
                        },100);
                    } else {
                        location.href="{{route('front_/')}}";
                    }
                } catch (e) {
                    console.error('Error parsing cart response:', e, result);
                    $('.card-detial-sec-main').html('<div class="alert alert-danger">Error loading cart. Please refresh the page.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading cart:', error);
                $('.card-detial-sec-main').html('<div class="alert alert-danger">Error loading cart. Please try again.</div>');
            }
        });
    }

    function removeFromCart(cart_id){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('front_remove-from-cart') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, cart_id : cart_id},
            beforeSend: function() {
                // Show loading indicator
                $('#qty'+cart_id).closest('.cart-item').css('opacity', '0.5');
            },
            success : function(result){
                getCartAjaxHtml();
                setCartItemCount();
            },
            error: function() {
                // Restore opacity on error
                $('#qty'+cart_id).closest('.cart-item').css('opacity', '1');
                Swal.fire("Error", "Could not remove item from cart. Please try again.", "error");
            }
        });
    }
    
    function setCartItemCount() {
        // Implement your cart count update logic here
        console.log("Cart item count should be updated");
        // You might need to make another AJAX call to get the updated count
        // or update a global variable that tracks the cart count
    }
});

let timerOn = true;
function timer(remaining) {
    var m = Math.floor(remaining / 60);
    var s = remaining % 60;
    m = m < 10 ? '0' + m : m;
    s = s < 10 ? '0' + s : s;
    if (document.getElementById('timer')) {
        document.getElementById('timer').innerHTML = m + ':' + s;
    }
    remaining -= 1;
    if(remaining >= 0 && timerOn) {
      setTimeout(function() {
          timer(remaining);
      }, 1000);
      return;
    }

    if(!timerOn) {
      return;
    }

    var is_otp_verify = $('#is_otp_verify').val();
    if(is_otp_verify == '0'){
        $('#resend_otp').show();
        $("#mobile").removeAttr("readonly"); 
        $('#resend_text').hide();
        $('#otp').hide();
    }
}
</script>
@endsection