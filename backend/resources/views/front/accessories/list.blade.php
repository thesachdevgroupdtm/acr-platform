@extends('front.layout.main')

@section('content')

<!-- shoping page start  -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('front/css/price-slider.css') }}">

<div class="shoping-breadcrum-bg shoping-category-bg">

    <div class="container">

        <ul class="shoping-breadcrum-main">

            <li><a href="{{url('accessories')}}">Accessories </a></li>

            <li><i class="fa-solid fa-chevron-right"></i></li>

            <li>Category</li>

        </ul>
<div class="wrapper_mobile_category">
    
<span class="mobile-filter-iconmain position-relative"><i class="fa-solid fa-filter"></i></span>

<div class="mobile-filter-mian">

    <h4 class="shop-category-heading">Categories</h4>

    <ul>

        @if($scategories->count())

            @foreach($scategories as $category)

                <li>

                    <?php /*<a for="pcategory{{$category->id}}" href="javascript:void(0);">

                        {{$category->name}}

                        <input class="form-check-input filter_category check-box-fliter" type="checkbox" value="{{$category->id}}" id="pcategory{{$category->id}}">

                    </a> */ ?>

                    <label class="filter-click-main" for="pcategory{{$category->id}}">
                    <input class="form-check-input filter_category check-box-fliter" type="checkbox" value="{{$category->id}}" id="pcategory{{$category->id}}"> 
                       <p> {{$category->name}}</p>

                        

                      

                    </label>

                </li>

            @endforeach

        @endif 

    </ul>

</div>
</div>
    </div>

</div>

<div class="shopping-section">

    <div class="container">

        <div class="row">

            <div class="col-12  col-md-4 col-lg-3 position-relative">

                <div class="fliter-main">
                    <div class="category-filter">
                        <h4 class="shop-category-heading">Categories</h4>

                        <ul>

                            @if($scategories->count())

                                @foreach($scategories as $category)

                                    <li>

                                        <?php /*<a for="pcategory{{$category->id}}" href="javascript:void(0);">

                                            {{$category->name}}

                                            <input class="form-check-input filter_category check-box-fliter" type="checkbox" value="{{$category->id}}" id="pcategory{{$category->id}}">

                                        </a> */ ?>

                                        <label class="filter-click-main" for="pcategory{{$category->id}}">
                                        <input class="form-check-input filter_category check-box-fliter" type="checkbox" value="{{$category->id}}" id="pcategory{{$category->id}}"> 
                                        <p>{{$category->name}}</p>

                                        </label>

                                    </li>

                                @endforeach

                            @endif

                        </ul>
                    </div>
                    <hr>
                    <div class="price-range-filter">
                        <h4 class="shop-category-heading">Filter By Price</h4>
                            <div class="custom-wrapper"> 
                                <div class="price-input-container"> 
                                    <div class="price-input"> 
                                        <div class="price-field"> 
                                            <span>Price:</span> 
                                            <span class="rupee-sign">₹</span><input type="number"
                                                class="min-input"
                                                value="0"><span class="dash-icon"></span>
                                            <span class="rupee-sign">₹</span><input type="number"
                                                class="max-input"
                                                value="8500"> 
                                        </div> 
                                        {{-- <button onclick="applyFilter()" class="btn btn-success">Filter</button> --}}
                                    </div> 
                                    <div class="slider-container"> 
                                        <div class="price-slider"> 
                                        </div> 
                                    </div> 
                                </div> 
                    
                                <!-- Slider -->
                                <div class="range-input"> 
                                    <input type="range"
                                        class="min-range"
                                        min="0"
                                        max="10000"
                                        value="0"
                                        step="1"> 
                                    <input type="range"
                                        class="max-range"
                                        min="0"
                                        max="10000"
                                        value="8500"
                                        step="1"> 
                                </div> 
                            </div> 
                        
                    </div>

                    <hr>
                    <div class="product-status-filter">
                        <h4 class="shop-category-heading">Product Status</h4>
                        <ul>
                            <li>
                                <label class="filter-click-main" for="instock">
                                <input class="form-check-input filter-product-stock check-box-fliter" type="checkbox" value="instock" id="instock"> 
                                <p> In Stock</p>
                            </li>
                            <li>
                                <label class="filter-click-main" for="onsale">
                                <input class="form-check-input filter-product-stock check-box-fliter" type="checkbox" value="onsale" id="onsale"> 
                                <p> On Sale</p>
                            </li>
                        </ul>
                    </div>

                </div>

            </div> 

            <div class=" col-12  col-md-8 col-lg-9">

                <div class="row" id="search_ajax_list">

                    @if($products)

                        @foreach($products as $product)

                            <div class="col-6 col-sm-6 col-md-6 col-lg-4 col-xxl-3">

                                <a href="{{url('accessories/'.$product->slug)}}">

                                    <div class="shoping-main-product">

                                        <?php /* @if(!empty($product->primaryImage->image) && isset($product->primaryImage->image))

                                            <img src="{{ $product->primaryImage->image }}"  alt="" title="{{ isset($product->primaryImage->image_title) ? $product->primaryImage->image_title : '' }}">

                                        @else

                                            <img src="{{ asset('front/img/no_image.jpg') }}" class="img-fluid" alt="" title="no_image">

                                        @endif */ ?>

                                        @if(isset($product->primaryImage->image) && $product->primaryImage->image)

                                            <img src="{{ asset('uploads/product/'.$product->id.'/'.$product->primaryImage->image) }}"  alt="" title="{{isset($product->primaryImage->image_title) ? $product->primaryImage->image_title : ''}}">

                                        @else

                                            <img src="{{ url('front/img/no_image.jpg') }}" class="img-fluid" alt="" title="no_image">

                                        @endif
                                            <div class="wrapper_shoping_cart">
                                            <div class="shoping-text-name">

<span class="product-title">{{$product->name}}</span>

<!-- <span class="product-category-title">Category : {{isset($product->shopCategoryDetail->name) ? $product->shopCategoryDetail->name : ''}}</span> -->
<span class="product-category-title"> {{isset($product->shopCategoryDetail->name) ? $product->shopCategoryDetail->name : ''}}</span>
</div> 

<div class="shoping-card-prise">

<div class="shoping-card-text"><p>₹{{formatNumber($product->price)}}</p></div>

<button class="shop-add-btn add_to_cart" type="button" data-product_id="{{$product->id}}">Add to cart</button>

<?php /*  <div class="shoping-star-group">

    <i class="fa-solid fa-star"></i>

    <i class="fa-solid fa-star"></i>

    <i class="fa-solid fa-star"></i>

    <i class="fa-solid fa-star"></i>

    <i class="fa-solid fa-star"></i>

</div> */ ?>

</div>



</div>

</a>
                                            </div>


                            </div>

                        @endforeach

                        <div class="pagination-main">

                            <div class="pagination justify-content-center">

                                {{ $products->links() }}

                            </div>

                        </div>

                    @endif

                </div>

            </div>

<!--            <div class="pagination-main">

                <div class="pagination">

                    <a href="#">&laquo;</a>

                    <a href="#">1</a>

                    <a href="#" class="active">2</a>

                    <a href="#">3</a>

                    <a href="#">4</a>

                    <a href="#">5</a>

                    <a href="#">6</a>

                    <a href="#">&raquo;</a>

                </div>

            </div>-->

        </div>

    </div>

</div>

<!-- shoping inner page end -->

@endsection

@section('javascript')
<script src="{{ asset('front/js/price-slider.js') }}"></script>
    <script>

        $(document).ready(function () {
            let delayTimer;
            $(document).on('click', '.pagination a',function(event){

                event.preventDefault();

                $('li').removeClass('active');

                $(this).parent('li').addClass('active');

                var myurl = $(this).attr('href');

                var page=$(this).attr('href').split('page=')[1];

                getSearchVals(page);

            });



            $(document).on('click', '.filter_category,.filter-product-stock ', function(){

                getSearchVals();

                // $(this).siblings('span').find('svg').toggleClass("fa-plus fa-minus");

            });


            $(".price-range-filter .range-input").on("input mousemove", function() {
                clearTimeout(delayTimer);
                delayTimer = setTimeout(getSearchVals, 1000);
            });


            $(document).on('click', '.add_to_cart', function(e){

                e.preventDefault();

                var product_id = $(this).data('product_id');

                /*swal({

                    title: "",

                    text: "Are you sure? You want to add this product to cart!",

                    type: "warning",

                    showCancelButton: true,

                    confirmButtonClass: "btn-danger",

                    confirmButtonText: "Yes",

                    cancelButtonText: "{{__('Cancel')}}",

                    closeOnConfirm: true

                },

                function(){

                    addItemToCart(product_id);

                });*/

                addItemToCart(product_id);

            });

        });



        $("#checkbox_lbl").click(function(){ 

            if($("#checkbox_id").is(':checked'))

                $("#checkbox_id").removAttr('checked');

            else

            $("#checkbox_id").attr('checked');

            });





        function getSearchVals(page = ''){
            var category = [];
            var priceRange=[];
            var productStatus=[];

            $('.filter_category:checked').each(function(i, e) {

                category.push($(this).val());

            });

            $('.price-range-filter .price-field input').each(function(i,e){
                priceRange.push(parseInt($(this).val()));
            });

            $('.product-status-filter input').each(function(i,e){
                var isChecked = $(this).prop('checked');
                if(isChecked){
                    productStatus.push($(this).val());
                }
                console.log(productStatus);
            });


            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

            if(page){

                var url = '{{ route("front_search-accessories-ajax") }}?page=' + page;

            } else {

                var url = '{{ route("front_search-accessories-ajax") }}';

            }

            $.ajax({

                type: "POST",

                url: url,

                data: {_token: CSRF_TOKEN, category: category,priceRange:priceRange,productStatus:productStatus},  

                success: function(result){

                    var result = $.parseJSON(result);

                    console.log(result);

                    $('#search_ajax_list').html(result.html);

                }

            });

        }



        function addItemToCart(product_id){

            var qty = 1;

            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

            $.ajax({

                url : '{{ route('front_add-to-cart') }}',

                method : 'post',

                data : {_token: CSRF_TOKEN, product_id : product_id, qty : qty},

                success : function(result){

                    toastr.success('', 'Item successfully added to cart!', { timeOut: 1000 });

                    setCartItemCount();

                }

            });

        }



        $('.mobile-filter-iconmain').click(function(){

            $('.mobile-filter-mian').toggle();

        });



        $('.filter-click-main').click(function(){

            $(this).find('svg').toggleClass("fa-plus fa-minus");

        });

        

    </script>

@endsection

