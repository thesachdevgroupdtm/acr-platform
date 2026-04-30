@php($subtotal = $producttotal = $servicetotal = 0)
@php($is_service_available = 0)
@php($is_product_available = 0)

@if($cart_data->count())
<div class="cart-items">
    @foreach($cart_data as $item)
    <div class="cart-item">
        <div class="cart-item-img">
            @if(isset($item->product_id) && $item->product_id)
                @php($is_product_available = 1)
                @if(isset($item->productDetail->primaryImage->image) && $item->productDetail->primaryImage->image)
                    <img src="{{ asset('uploads/product/'.$item->productDetail->id.'/'.$item->productDetail->primaryImage->image) }}" alt="{{ isset($item->productDetail->primaryImage->image_title) ? $item->productDetail->primaryImage->image_title : '' }}">
                @else
                    <img src="{{ asset('front/img/no_image.jpg') }}" alt="">
                @endif
            @endif
            @if(isset($item->service_id) && $item->service_id)
                @php($is_service_available = 1)
                @if(isset($item->serviceDetail->packageDetail->image) && $item->serviceDetail->packageDetail->image)
                    <img src="{{ asset('uploads/service/package/'.$item->serviceDetail->packageDetail->image )}}" alt="">
                @else
                    <img src="{{ asset('front/img/no_image.jpg') }}" alt="">
                @endif
            @endif
        </div>
        <div class="cart-item-details">
            <div class="cart-item-name">
                @if(isset($item->product_id) && $item->product_id)
                    {{isset($item->productDetail->name) ? $item->productDetail->name : NULL}}
                @endif
                @if(isset($item->service_id) && $item->service_id)
                    {{isset($item->serviceDetail->packageDetail->title) ? $item->serviceDetail->packageDetail->title : NULL}}
                @endif
            </div>
            
            @if (isset($item->serviceDetail->packageDetail->categoryDetail->title))
            <div class="service-category">
                <span class="badge">{{ $item->serviceDetail->packageDetail->categoryDetail->title }}</span>
            </div>
            @endif
            
            <div class="cart-item-meta">
                @if(isset($item->serviceDetail->brandDetail->title))
                    <span class="car-detail">{{$item->serviceDetail->brandDetail->title}}</span> •
                @endif
                @if(isset($item->serviceDetail->modelDetail->title))
                    <span class="car-detail">{{$item->serviceDetail->modelDetail->title}}</span> •
                @endif
                @if(isset($item->serviceDetail->fuelTypeDetail->title))
                    <span class="car-detail">{{$item->serviceDetail->fuelTypeDetail->title}}</span>
                @endif
            </div>
            
            <div class="cart-item-price">
                @php($qty = $item->qty)
                @if(isset($item->product_id) && $item->product_id)
                    @php($unit_price = isset($item->productDetail->price) && $item->productDetail->price ? $item->productDetail->price : 0)
                    @php($item_total = $qty * $unit_price)
                    @php($subtotal = $subtotal + $item_total)
                    @php($producttotal = $producttotal + $item_total)
                    ₹{{formatNumber($unit_price)}} × {{$qty}} = ₹{{formatNumber($item_total)}}
                @endif
                
                @if(isset($item->service_id) && $item->service_id)
                    @php($unit_price = isset($item->serviceDetail->price) && $item->serviceDetail->price ? $item->serviceDetail->price : 0)
                    @php($item_total = $qty * $unit_price)
                    @php($subtotal = $subtotal + $item_total)
                    @php($servicetotal = $servicetotal + $item_total)
                    ₹{{formatNumber($unit_price)}} × {{$qty}} = ₹{{formatNumber($item_total)}}
                @endif
            </div>
            
            <div class="quantity-control">
                <button type="button" class="qty-btn minus-btn" data-id="{{$item->id}}" 
                    @if(isset($item->product_id) && $item->product_id) data-product_id="{{$item->product_id}}" @endif
                    @if(isset($item->service_id) && $item->service_id) data-service_id="{{$item->service_id}}" @endif>
                    <i class="fas fa-minus"></i>
                </button>
                <input type="text" class="qty-input" id="qty{{$item->id}}" value="{{$qty}}" readonly>
                <button type="button" class="qty-btn plus-btn" data-id="{{$item->id}}" 
                    @if(isset($item->product_id) && $item->product_id) data-product_id="{{$item->product_id}}" @endif>
                    <i class="fas fa-plus"></i>
                </button>
                
                <a href="javascript:void(0)" class="cart-remove minus-btn" data-id="{{$item->id}}" 
                    @if(isset($item->product_id) && $item->product_id) data-product_id="{{$item->product_id}}" @endif
                    @if(isset($item->service_id) && $item->service_id) data-service_id="{{$item->service_id}}" @endif>
                    <i class="fas fa-trash"></i> Remove
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="order-summary">
    <h3 class="summary-title">Order Summary</h3>
    
    <div class="summary-line">
        <span>Item Total</span>
        <span>₹{{formatNumber($subtotal)}}</span>
    </div>
    
    @php($pgst_val = $sgst_val = 0)
    @if($is_product_available == 1)
        @php($pgst_val = $product_gst && $producttotal ? ($producttotal*$product_gst)/100 : 0)
        <div class="summary-line">
            <span>
                @if($is_product_available == 1 && $is_service_available == 1) 
                    Product GST({{$product_gst}}%)
                @else
                    GST({{$product_gst}}%)
                @endif
            </span>
            <span>₹{{formatNumber($pgst_val)}}</span>
        </div>
    @endif
    
    @if($is_service_available == 1)
        @php($sgst_val = $service_gst && $servicetotal ? ($servicetotal*$service_gst)/100 : 0)
        <div class="summary-line">
            <span>
                @if($is_service_available == 1 && $is_product_available == 1) 
                    Service GST({{$service_gst}}%)
                @else
                    GST({{$service_gst}}%)
                @endif
            </span>
            <span>₹{{formatNumber($sgst_val)}}</span>
        </div>
    @endif
    
    @php($total = $subtotal + $pgst_val + $sgst_val)
    <div class="summary-line total">
        <span>Total Amount</span>
        <span>₹{{formatNumber($total)}}</span>
    </div>
    
    <input type="hidden" name="subtotal" value="{{$subtotal}}">
    <input type="hidden" name="product_gst" value="{{$pgst_val}}">
    <input type="hidden" name="service_gst" value="{{$sgst_val}}">
    <input type="hidden" name="order_total" value="{{$total}}">
    <input type="hidden" name="is_service_in_cart" value="{{$is_service_available}}">
    
    <div class="cart-action-buttons">
        <button class="btn-loading d-none" id="loading_btn">
            <i class="fas fa-spinner fa-spin"></i>
            Processing Your Order...
        </button>
        <button class="btn-primary" id="booking_confirm" type="submit">
            <i class="fas fa-check-circle"></i> CONFIRM BOOKING
        </button>
    </div>
</div>

<style>
.cart-items {
    margin-bottom: 25px;
}
.cart-item {
    display: flex;
    padding: 20px 0;
    border-bottom: 1px solid var(--border-color);
    align-items: flex-start;
}
.cart-item:last-child {
    border-bottom: none;
}
.cart-item-img {
    width: 90px;
    height: 90px;
    border-radius: 10px;
    overflow: hidden;
    margin-right: 18px;
    flex-shrink: 0;
    border: 1px solid var(--border-color);
}
.cart-item-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.cart-item-details {
    flex: 1;
}
.cart-item-name {
    font-weight: 700;
    margin-bottom: 8px;
    font-size: 17px;
    color: var(--dark-color);
}
.service-category {
    margin: 8px 0;
}
.badge {
    background: var(--primary-light);
    color: var(--primary-color);
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.cart-item-meta {
    font-size: 14px;
    color: var(--gray-medium);
    margin-bottom: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.car-detail {
    background: var(--gray-light);
    padding: 3px 8px;
    border-radius: 4px;
}
.cart-item-price {
    font-weight: 700;
    color: var(--primary-color);
    font-size: 16px;
    margin-bottom: 15px;
}
.quantity-control {
    display: flex;
    align-items: center;
    gap: 12px;
}
.qty-btn {
    width: 34px;
    height: 34px;
    background: var(--light-color);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-weight: bold;
    color: var(--dark-color);
    transition: all 0.3s;
}
.qty-btn:hover {
    background: var(--primary-light);
    border-color: var(--primary-color);
    color: var(--primary-color);
}
.qty-input {
    width: 45px;
    height: 34px;
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-weight: 600;
}
.cart-remove {
    font-size: 14px;
    color: #e53e3e !important;
    text-decoration: none;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: 15px;
}
.cart-remove:hover {
    color: #c53030 !important;
}
.order-summary {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 2px solid var(--border-color);
}
.summary-title {
    font-size: 19px;
    font-weight: 700;
    margin-bottom: 22px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
    color: var(--dark-color);
}
.summary-line {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color);
    font-size: 15px;
}
.summary-line.total {
    font-weight: 700;
    font-size: 18px;
    border-bottom: none;
    padding-top: 18px;
    color: var(--primary-color);
}
.cart-action-buttons {
    margin-top: 25px;
}
.btn-loading {
    background: #f7fafc;
    color: var(--gray-medium);
    border: 1px solid var(--border-color);
    padding: 16px;
    border-radius: 8px;
    font-weight: 600;
    width: 100%;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
</style>
@else
<div class="empty-cart-message">
    <i class="fas fa-shopping-cart"></i>
    <h3>Your cart is empty</h3>
    <p>Please add some items to proceed with checkout</p>
</div>
@endif