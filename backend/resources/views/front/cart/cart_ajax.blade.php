        <section class="page-header">
            <div class="page-header__bg"></div>
            <!-- /.page-header__bg -->
            <div class="container">
                <h2 class="page-header__title bw-split-in-right">Cart</h2>
                <ul class="karoons-breadcrumb list-unstyled">
                    <li><a href="index.html"><i class="flaticon-home"></i>Home</a></li>
                    <li><span>Cart</span></li>
                </ul><!-- /.thm-breadcrumb list-unstyled -->
            </div><!-- /.container -->
        </section><!-- /.page-header -->

        <section class="cart-page">
            <div class="container">
                @php($subtotal = $producttotal = $servicetotal = 0)
                @php($is_service_available = 0)
                @php($is_product_available = 0)

                @if($cart_data->count())

                {{-- Selected Car Info --}}
                @foreach($cart_data as $item)
                @if(isset($item->service_id) && $item->service_id)
                <div class="table-responsive mb-4">
                    <table class="table cart-page__table">
                        <thead>
                            <tr>
                                <th colspan="3">Selected Car</th>
                            </tr>
                            <tr>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Fuel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $item->serviceDetail->brandDetail->title ?? 'N/A' }}</td>
                                <td>{{ $item->serviceDetail->modelDetail->title ?? 'N/A' }}</td>
                                <td>{{ $item->serviceDetail->fuelTypeDetail->title ?? 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @break
                @endif
                @endforeach

                {{-- Cart Items --}}
                <div class="table-responsive">
                    <table class="table cart-page__table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Sub Total</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart_data as $item)
                            @php($qty = $item->qty)

                            {{-- Product Items --}}
                            @if(isset($item->product_id) && $item->product_id)
                            @php($is_product_available = 1)
                            @php($unit_price = $item->productDetail->price ?? 0)
                            @php($item_total = $qty * $unit_price)
                            @php($subtotal += $item_total)
                            @php($producttotal += $item_total)

                            <tr id="cart-item-{{$item->id}}">
                                <td>
                                    <div class="cart-page__table__meta">
                                        <div class="cart-page__table__meta-img">
                                            @if(isset($item->productDetail->primaryImage->image))
                                            <img src="{{ asset('uploads/product/'.$item->productDetail->id.'/'.$item->productDetail->primaryImage->image) }}"
                                                alt="{{ $item->productDetail->primaryImage->image_title ?? '' }}">
                                            @else
                                            <img src="{{ asset('front/img/no_image.jpg') }}" alt="No Image">
                                            @endif
                                        </div>
                                        <h3 class="cart-page__table__meta-title">
                                            {{ $item->productDetail->name ?? '' }}
                                        </h3>
                                    </div>
                                </td>
                                <td>₹{{ formatNumber($unit_price) }}</td>
                                <td>
                                    <div class="product-details__quantity">
                                        <div class="quantity-box">
                                            <button type="button" class="sub minus-btn" data-id="{{$item->id}}" data-product_id="{{$item->product_id}}">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="text" id="qty{{$item->id}}" value="{{ $qty }}" readonly class="cart-quantity">
                                            <button type="button" class="add plus-btn" data-id="{{$item->id}}" data-product_id="{{$item->product_id}}">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td id="item-total-{{$item->id}}">₹{{ formatNumber($item_total) }}</td>
                                <td>
                                    <a href="javascript:void(0);" class="cart-page__table__remove remove-btn" data-id="{{$item->id}}" data-type="product" data-product_id="{{$item->product_id}}">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </td>
                            </tr>
                            @endif

                            {{-- Service Items --}}
                            @if(isset($item->service_id) && $item->service_id)
                            @php($is_service_available = 1)
                            @php($unit_price = $item->serviceDetail->price ?? 0)
                            @php($item_total = $qty * $unit_price)
                            @php($subtotal += $item_total)
                            @php($servicetotal += $item_total)

                            <tr id="cart-item-{{$item->id}}">
                                <td>
                                    <div class="cart-page__table__meta">
                                        <div class="cart-page__table__meta-img">
                                            @if(isset($item->serviceDetail->packageDetail->image))
                                            <img src="{{ asset('uploads/service/package/'.$item->serviceDetail->packageDetail->image )}}" alt="">
                                            @else
                                            <img src="{{ asset('front/img/no_image.jpg') }}" alt="No Image">
                                            @endif
                                        </div>
                                        <h3 class="cart-page__table__meta-title">
                                            {{ $item->serviceDetail->packageDetail->title ?? '' }}
                                        </h3>
                                    </div>
                                </td>
                                <td>₹{{ formatNumber($unit_price) }}</td>
                                <td>
                                    <div class="product-details__quantity">
                                        <div class="quantity-box">
                                            <button type="button" class="sub minus-btn" data-id="{{$item->id}}" data-service_id="{{$item->service_id}}">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="text" id="qty{{$item->id}}" value="{{ $qty }}" readonly class="cart-quantity">
                                            <button type="button" class="add plus-btn" data-id="{{$item->id}}" data-service_id="{{$item->service_id}}">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td id="item-total-{{$item->id}}">₹{{ formatNumber($item_total) }}</td>
                                <td>
                                    <a href="javascript:void(0);" class="cart-page__table__remove remove-btn" data-id="{{$item->id}}" data-type="service" data-service_id="{{$item->service_id}}">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="table-responsive mt-4">
                    @php($pgst_val = $sgst_val = 0)
                    @if($is_product_available)
                    @php($pgst_val = $product_gst && $producttotal ? ($producttotal*$product_gst)/100 : 0)
                    @endif
                    @if($is_service_available)
                    @php($sgst_val = $service_gst && $servicetotal ? ($servicetotal*$service_gst)/100 : 0)
                    @endif
                    @php($total = $subtotal + $pgst_val + $sgst_val)

                    <div class="table-responsive mt-4">
                        <table class="table cart-page__table">
                            <thead>
                                <tr>
                                    <th colspan="3">Cart Total</th>
                                </tr>
                                <tr>
                                    <th>Subtotal</th>
                                    @if($is_product_available)
                                    <th>Product GST ({{$product_gst}} %)</th>
                                    @endif
                                    @if($is_service_available)
                                    <th>Service GST ({{$service_gst}} %)</th>
                                    @endif
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>₹{{ formatNumber($subtotal) }}</td>
                                    @if($is_product_available)
                                    <td>₹{{ formatNumber($pgst_val) }}</td>
                                    @endif
                                    @if($is_service_available)
                                    <td>₹{{ formatNumber($sgst_val) }}</td>
                                    @endif
                                    <td><strong>₹{{ formatNumber($total) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="cart-page__buttons">
                    <a href="{{ url('checkout') }}" class="karoons-btn karoons-btn--base">
                        <span>Proceed To Checkout</span>
                    </a>
                </div>
                @else
                <div class="alert alert-info">
                    Your cart is empty. <a href="{{ url('/') }}">Continue shopping</a>
                </div>
                @endif
            </div>
        </section>