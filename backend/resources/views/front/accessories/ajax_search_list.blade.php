@if($products->count())
    @foreach($products as $product)
        <div class="col-6 col-sm-6 col-md-6 col-lg-4 col-xxl-3">
            <a href="{{url('accessories/'.$product->slug)}}">
                <div class="shoping-main-product">
                    @if(isset($product->primaryImage->image) && $product->primaryImage->image)
                        <img src="{{ asset('uploads/product/'.$product->id.'/'.$product->primaryImage->image) }}" class="img-fluid" alt="" title="">
                    @else
                        <img src="{{ asset('front/img/no_image.jpg') }}" class="img-fluid" alt="" title="">
                    @endif
                    <div class="wrapper_shoping_cart">
                        <div class="shoping-text-name">
                            <span class="product-title">{{$product->name}}</span>
                            <span class="product-category-title">{{isset($product->shopCategoryDetail->name) ? $product->shopCategoryDetail->name : ''}}</span>
                        </div> 
                        <div class="shoping-card-prise">
                                <?php /*<div class="shoping-star-group">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div> */ ?>
                            <div class="shoping-card-text"><p>₹{{$product->price}}</p></div>
                            <button class="shop-add-btn add_to_cart" type="button" data-product_id="{{$product->id}}">Add to cart</button>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
    <div class="pagination-main">
        <div class="pagination justify-content-center">
            {{ $products->links() }}
        </div>
    </div>
@else
<div class=" col-12  col-md-8 col-lg-9 no-products">There is no matching product available.</div>
@endif