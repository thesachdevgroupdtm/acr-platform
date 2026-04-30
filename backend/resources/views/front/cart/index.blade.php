@extends('front.layout.main')

@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Make sure Font Awesome is included -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endsection

@section('content')

<div class="wrapper_cart_item">
    <form method="POST" action="{{route('front_create-order')}}" id="checkout-form" enctype="multipart/form-data" data-parsley-validate="">
        @csrf
        <div class="m-0 card-detial-sec-main">
            <!-- Cart content will be loaded here via AJAX -->
            <div class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Loading cart...</p>
            </div>
        </div>
    </form>
</div>

@endsection

@section('javascript')
<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
<!-- Include SweetAlert for confirmation dialogs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
    // Initialize with a console log to check if JS is working
    console.log("Cart page loaded");
    
    getCartAjaxHtml();

    // Plus button click event
    $(document).on('click', '.plus-btn', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var inputField = $('#qty'+id);
        var currentVal = parseInt(inputField.val());
        
        if (!isNaN(currentVal)) {
            inputField.val(currentVal + 1);
            updateCart(id, currentVal + 1);
        }
    });

    // Minus button click event
    $(document).on('click', '.minus-btn', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var inputField = $('#qty'+id);
        var currentVal = parseInt(inputField.val());
        
        if (!isNaN(currentVal) && currentVal > 1) {
            inputField.val(currentVal - 1);
            updateCart(id, currentVal - 1);
        }
    });

    // Remove button click event
    $(document).on('click', '.remove-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        
        var id = $(this).data('id');
        var type = $(this).data('type'); // product or service
        
        Swal.fire({
            title: "Are you sure?",
            text: "You want to delete this item from your cart!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Yes, remove it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                removeFromCart(id, type);
            }
        });
    });

    function updateCart(cart_id, qty){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        
        $.ajax({
            url: '{{ route("front_update-cart") }}',
            method: 'post',
            data: {
                _token: CSRF_TOKEN, 
                cart_id: cart_id, 
                qty: qty
            },
            beforeSend: function() {
                // Show loading indicator if needed
                $('#cart-item-'+cart_id).css('opacity', '0.7');
            },
            success: function(result){
                getCartAjaxHtml();
            },
            error: function(xhr, status, error) {
                console.error('Error updating cart:', error);
                getCartAjaxHtml(); // Refresh cart to show correct values
            }
        });
    }

    function getCartAjaxHtml(){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        var loggedin_user_mobile = "{{ Auth::guard('user')->check() ? Auth::guard('user')->user()->phone : ''}}";

        $.ajax({
            url: '{{ route("front_cart-ajax-html") }}',
            method: 'post',
            data: {_token: CSRF_TOKEN},
            beforeSend: function() {
                // Show loading indicator if needed
                $('.card-detial-sec-main').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading cart...</p></div>');
            },
            success: function(result){
                try {
                    console.log("Cart HTML received");
                    var parsedResult = typeof result === 'string' ? JSON.parse(result) : result;
                    
                    if(parsedResult.status == 'success'){
                        $('.card-detial-sec-main').html(parsedResult.html);
                        
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
                            } else {
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
                        }, 100);
                    } else {
                        console.log("Redirecting to home");
                        location.href = "{{route('front_/')}}";
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

    function removeFromCart(cart_id, type){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        
        $.ajax({
            url: '{{ route("front_remove-from-cart") }}',
            method: 'post',
            data: {
                _token: CSRF_TOKEN, 
                cart_id: cart_id,
                type: type
            },
            beforeSend: function() {
                // Show loading on the specific item
                $('#cart-item-'+cart_id).css('opacity', '0.5');
            },
            success: function(result){
                getCartAjaxHtml();
                updateCartItemCount();
            },
            error: function(xhr, status, error) {
                console.error('Error removing item:', error);
                $('#cart-item-'+cart_id).css('opacity', '1');
                Swal.fire("Error", "Could not remove item from cart. Please try again.", "error");
            }
        });
    }
    
    // Function to update cart item count
    function updateCartItemCount() {
        // Simple implementation - count the quantity inputs
        var itemCount = $('.cart-quantity').length;
        if ($('.cart-count-badge').length) {
            $('.cart-count-badge').text(itemCount);
        }
    }
});

// Timer function for OTP (if needed)
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