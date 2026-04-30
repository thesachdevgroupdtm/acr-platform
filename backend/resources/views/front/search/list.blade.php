@extends('front.layout.main')
@section('content')
<!-- shoping page start  -->
<div class="shoping-breadcrum-bg shoping-category-bg">
    <div class="container">
        <ul class="shoping-breadcrum-main">
            <li><a href="{{url('accessories')}}">Search Items </a></li>
            <!--<li><i class="fa-solid fa-chevron-right"></i></li>-->
            <!--<li>Product</li>-->
        </ul>
    </div>
</div>

<div class="shopping-section">
<div class="container"> 
    <div class="search-page-bg">

        <!-- start product -->
   @if(count($products)>0 || count($schedulepackage)>0)
    @if($products)
    @foreach($products as $product)
        <div class="card mb-3">
            <div class="row p-4">
                    
                    <div class="col-12 col-sm-6 col-lg-3">
                         @if(!empty($product->primaryImage) && isset($product->primaryImage->image))
                         <img src="{{ $product->primaryImage->image }}" class="search-list-image"   alt="" title="{{ isset($product->primaryImage->image_title) ? $product->primaryImage->image_title : '' }}">
                         @else
                        <img src="{{ asset('front/img/no_image.jpg') }}" class="search-list-image" alt="" title="no_image">
                        @endif
                    </div>

                    <div class="col-12 col-sm-6 col-lg-6">
                        <div>
                            <h4 class="search-list-head"><a href="{{url('accessories/'.$product->slug)}}" style="text-decoration: none;color: black;">{{$product->name}}</a></h4>
                        </div>

                         <div>
                            <h6 class="search-list-head">{{isset($product->shopCategoryDetail->name) ? $product->shopCategoryDetail->name : ''}}</h6>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="mb-3">
                            <span class="prise-total">₹{{formatNumber($product->price)}}</span>
                        </div>
                        
                        <div>
                            <button class="shop-add-btn add_to_cart btn detail-btn-main" data-product_id="{{$product->id}}">Add to cart</button>
                        </div>
                    </div>
            </div>
        </div>

    @endforeach
    @endif
<!-- end product -->

    <!-- services -->

    @if(isset($schedulepackage) && $schedulepackage->count())
    @foreach($schedulepackage as $package)
        <div class="card mb-3">
            <div class="row p-4">

                    <div class="col-12 col-sm-6 col-lg-3">
                        @if(isset($package->image) && $package->image)
                        <img src="{{ url($package->image )}}" class="search-list-image" alt="" title="no_image">
                        @else
                                <img src="{{ asset('front/img/inner-palish-service.png') }}" class="img-fluid" alt="" title="">
                        @endif
                    </div>

                    <div class="col-12 col-sm-6 col-lg-6">
                        <div>
                            <h4 class="search-list-head">{{$package->title}}</h4>
                        </div>

                         <div>
                            <h6 class="search-list-head">{{isset($package->categoryDetail->title) ? $package->categoryDetail->title : ''}}</h6>
                        </div>
                         <div class="search-list-main-dot">
                            <div>
                                @if($package->recommended_info)
                                <div class="search-inner-text">   
                                    <span class="text-primary"> • </span>
                                    <span>{!!$package->recommended_info!!}</span><br>
                                </div>
                                @endif
                                @if($package->warrenty_info)
                                <div class="search-inner-text">
                                    <span class="text-primary"> • </span>
                                    <span>{!!$package->warrenty_info!!}</span><br>
                                </div>
                                @endif
                                @if($package->note)
                                <div class="search-inner-text">
                                    <span class="text-primary"> • </span>
                                    <span>{!!$package->note!!}</span>
                                </div>
                                @endif

                                @if(isset($package->specifications))
                                @foreach($package->specifications as $skey => $srecord)
                                <div class="col-12 search-inner-text  basic-service-text-main spacification s{{$package->id}} @if($skey > 4) {{'d-none'}} @endif" >
                                    <span class="text-primary"> • </span>
                                    <span>{{$srecord->specification}}</span><br>
                                </div>
                                @endforeach
                                @endif

                                @if($package->specifications->count() > 5)
                                <div class="col-12 col-sm-3" id="more{{$package->id}}">
                                    <a href="javascript:void(0)" data-id="{{$package->id}}" class="more"><small>+{{ $package->specifications->count() - 5 }} More View All</small></a> 
                                </div>
                                 @endif
                            </div>
                    
                        </div>
                        
                    </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="mb-3">
                                    @if($package->time_takes !==null)
                                    <span><i class="fa fa-clock"></i>&nbsp;{{$package->time_takes}} hrs Taken</span>
                                    @else
                                    <span><i class="fa fa-clock"></i>&nbsp;{{$package->time_takes_day}} Days Taken</span>
                                    @endif
                                </div>
                                
                                <div>

                                   <a class="apt-btn serin-appointment-btn btn detail-btn-main" href="javascript:void(0)">Book A Service</a>
                                </div>
                            </div>
                        </div>
                    </div>

        @endforeach
    @endif
    @else
    
        <p style="text-align: center;font-weight: bold;font-size: 18px;">No Result found!</p>
    
    @endif
</div>
</div>


@endsection
@section('javascript')
    <script>
        $(document).ready(function () {
        
            $(document).on('click', '.add_to_cart', function(){
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


        function addItemToCart(product_id){
            var qty = 1;
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url : '{{ route('front_add-to-cart') }}',
                method : 'post',
                data : {_token: CSRF_TOKEN, product_id : product_id, qty : qty},
                success : function(result){
                    toastr.success('Item successfully added to cart!');
                    setCartItemCount();
                }
            });
        }

        $('.mobile-filter-iconmain').click(function(){
            $('.mobile-filter-mian').toggle();
        });

        $(document).on('click', '.more', function(){
        var id = $(this).data('id');
        $('.s'+id).removeClass('d-none');
        $('#more'+id).remove();
    });

    </script>
@endsection