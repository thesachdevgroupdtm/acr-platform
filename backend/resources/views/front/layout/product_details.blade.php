@extends('front.layout.main')
@section('meta_description',$product->meta_description)
@section('meta_tags',$product->meta_tags)
@push('custom-scripts')
    <script type="text/javascript">{!! $product->schema_tags !!}</script>
@endpush
@section('css')
    <link rel="stylesheet" href="{{ url('public/front/css/flexslider.css') }}" type="text/css" media="screen" />
@endsection
@section('content')
<div>
    <div>
        <div class="productdetailpage">
            <div class="producttitle">
                <ul class="m-0">
                    <li><a href="{{ url('/') }}">HOME</a> <i class="fa-solid fa-chevron-right"></i></li>
                    <li class="categorytext"><a href="{{ url('/products/'.$category->slug) }}">{{ $category->title }}</a> <i class="fa-solid fa-chevron-right"></i></li>
                    <li class="categorytext"><a href="{{ url('/products/'.$category->slug.'/'.$product->productCategory->slug) }}">{{ $product->productCategory->title }}</a> <i class="fa-solid fa-chevron-right"></i></li>
                    <li class="categorytext">{{ $product->product_name }}</li>
                </ul>
            </div>
            <div>
                <div class="productdetailpage-container">
                    <div class="productdetailcontent">
                        <div class="row rowcommon">
                            <div class="col-12 col-md-5">
                                <div class="productimg">
                                    <?php /* <img src="{{ asset('public/uploads/'.$product->image) }}" class="img-fluid" alt=""> */ ?>
                                    @if(isset($product->productImages) && !empty($product->productImages))
                                        <div id="slider" class="flexslider">
                                            <ul class="slides">
                                                @foreach($product->productImages as $image)
                                                    <li><img class="image-fluid" src="{{ getAWS_S3BucketUrl().$image->image }}" alt="{{$product->alt_tag}}" /></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <div id="carousel" class="flexslider">
                                            <ul class="slides">
                                                @foreach($product->productImages as $image)
                                                    <li><img src="{{ getAWS_S3BucketUrl().$image->image }}" alt="{{$product->alt_tag}}" /></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <button class="sharebtn" data-bs-toggle="modal" data-bs-target="#share" data-product_id="{{ $product->id }}"><i class="fa-regular fa-share-from-square"></i></button>
                                    <a href="{{ getAWS_S3BucketUrl().$product->file }}" target="_blank"><button class="pdfbtn"><i class="fa-regular fa-file-pdf"></i></button></a>
                                    <button class="enquirebtn" data-bs-toggle="modal" data-bs-target="#enquire">ENQUIRE NOW</button>
                                    <!--<button class="enquirebtn" data-bs-toggle="modal" data-bs-target="#heat-input-calculator">Heat input calculator</button>-->
                                </div>
                            </div>
                            <div class="col-12 col-md-7">
                                <div class="maxfilcontent">
                                    <h2>{{ $product->productCategory->title }}</h2>
                                    <h2>{{ $product->product_name }}</h2>
                                    <h6>AWS: SFA {{ $product->sfa }} {{ $product->aws_code }} </h6>
                                    <h6>EN ISO CODE: {{ $product->en_iso_code }} </h6>
                                    {!! $product->characteristics !!}
                                </div>
                                <div class="product-approval">
                                    <b>Approvals</b>
                                    <div class="row">
                                        @foreach($product->productsProductApprovar as $approval)
                                            <div class="col-md-4 approval-product-text">{{ $approval->productApproval->name }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                @foreach($product->productTables as $table)
                                    <div class="product_table_bg">
                                        <div class="bg_sec_table">
                                            {{ $table->title }}
                                        </div>
                                        <div class="table-responsive">
                                            {!! $table->table !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @if(isset($product->video_link) && !empty($product->video_link))
                        <iframe width="100%"
                                height="500"
                                src="{{ $product->video_link }}?controls=0&autoplay=0&&loop=1&mute=1"
                                allow="autoplay"
                                title="YouTube video player"
                                frameborder="0"
                                allowfullscreen>
                        </iframe>
                    @endif
                </div>
            </div>
            <div class="productgallery">
                <div>
                    <div class="container">
                        <h4>{{ $product->productCategory->title }}</h4>
                        <div class="row rowcommon">
                            @foreach($related_products as $related_product)
                                <div class="col-12 col-sm-6 col-md-4 mb-2">
                                    <div class="row">
                                        <div class="col-4">
                                            <img src="{{ getAWS_S3BucketUrl().$related_product->image }}" class="img-fluid" alt="{{ $related_product->alt_tag }}">
                                        </div>
                                        <div class="col-8">
                                            <div class="productgallerycontent">
                                                <a href="{{ url('products/'.$category_slug.'/'.$sub_category_slug.'/'.$related_product->slug) }}"><h5>{{ $related_product->product_name }}</h5></a>
                                                <p>{{ $related_product->aws_code }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- share popup start  -->
<!-- Modal -->
<div class="modal fade" id="share" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class=" modeltopborder">
                <span data-bs-dismiss="modal" class="close">x</span>
            </div>
            <div class="modal-body ">
                <div>
                    <p class="getpophead"> Get the product details over an email</p>
                </div>
                <div class="share-email">
                    <div>
                        <img src="{{ asset('front/img/logo.png') }}"  class="p-3 share-emil-logo"  alt="">
                    </div>
                    <div>
                        <form class="share-form">
                            <input type="hidden" name="product_id" id="product_id" value="{{ $product->id }}" />
                            <label for="exampleInputEmail1" class="form-label">EMAIL ADDRESS </label>
                            <input type="text" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
                            <div class="error" id="error-exampleInputEmail1"></div>
                            <label for="exampleInputWhatsApp1" class="form-label">WhatsApp Number</label>
                            <input type="text" class="form-control" id="exampleInputWhatsApp1" maxlength="10" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" aria-describedby="emailHelp">
                            <div class="error" id="error-exampleInputWhatsApp1"></div>
                            <button type="submit" class="submitbtn mt-3">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- share popup end  -->

<!-- pop up enquire start  -->
<div class="modal fade" id="enquire" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="closebtn">
                <span data-bs-dismiss="modal" class="close">x</span>
            </div>
            <div class="modal-body">
                <div>
                    <p class=" text-center">Enquire Form </p>
                    <form id="enquire-form">
                        <input type="hidden" name="product_id" id="product_id" value="{{ $product->id }}" />
                        <div class="mb-3">
                            <label for="name" class="form-label">NAME</label>
                            <input type="text" class="form-control" name="name" id="name" aria-describedby="emailHelp">
                            <div class="error" id="error-name"></div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">PHONE</label>
                            <input type="tel" class="form-control" name="phone" id="phone" maxlength="10" aria-describedby="emailHelp">
                            <div class="error" id="error-phone"></div>
                        </div>
                        <div class="mb-3">
                            <label for="email_id" class="form-label">EMAIL ID</label>
                            <input type="email" class="form-control" name="email_id" id="email_id" aria-describedby="emailHelp">
                            <div class="error" id="error-email_id"></div>
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">CITY</label>
                            <input type="text" class="form-control" name="city" id="city" aria-describedby="emailHelp">
                            <div class="error" id="error-city"></div>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">QUANTITY</label>
                            <input type="number" class="form-control" name="quantity" id="quantity" aria-describedby="emailHelp">
                            <div class="error" id="error-quantity"></div>
                        </div>
                        <button type="submit" class="submitbtn">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- pop up enquire end  -->
<!-- share popup end  -->
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection

@section('javascript')
<script defer src="{{ url('public/front/js/jquery.flexslider.js') }}"></script>
<script src="{{ asset('plugins/notification/toastr.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#carousel').flexslider({
            animation: "slide",
            controlNav: false,
            animationLoop: true,
            slideshow: false,
            itemWidth: 100,
            itemMargin: 5,
            asNavFor: '#slider'
        });
        
        $('#slider').flexslider({
            animation: "slide",
            controlNav: false,
            animationLoop: true,
            slideshow: false,
            sync: "#carousel",
        });
        
        $(".share-form button[type='submit']").click(function(e) {
            e.preventDefault();
            var error = 0;
            var exampleInputEmail1 = $("#exampleInputEmail1").val();
            var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            $("#error-exampleInputEmail1").html("");
            if(exampleInputEmail1=="") {
                $("#error-exampleInputEmail1").html("Email id is required.");
                error = 1;
            }
            if(!filter.test(exampleInputEmail1) && exampleInputEmail1!="" ){
                $("#error-exampleInputEmail1").html("Please enter a valid email address.");
                error = 1;
            }
            var exampleInputWhatsApp1 = $('#exampleInputWhatsApp1').val();
            if(exampleInputWhatsApp1=="") {
                $("#error-exampleInputWhatsApp1").html("WhatsApp Number is required.");
                error = 1;
            }
            
            if(!error) {
                var product_id = $("#share #product_id").val();
                $.ajax({
                    type : "POST",
                    data : { _token: "{{ csrf_token() }}", email: exampleInputEmail1, whatsapp_number: exampleInputWhatsApp1, product_id: product_id },
                    url  : "{{ url('share-product') }}",
                    dataType : 'JSON',
                    success : function(response) {
                        $("#share").modal('hide');
                        toastr.success(response.message);
                    }
                });
            }
        }); 
            
        $("#enquire-form button[type='submit']").click(function(e) {
            e.preventDefault();
            var error = 0;
            
            var name = $("#name").val();
            $("#error-name").html("");
            if(name=="") {
                $("#error-name").html("Name is required.");
                error = 1;
            }
            
            var phone = $("#phone").val();
            $("#error-phone").html("");
            if(phone=="") {
                $("#error-phone").html("Phone is required.");
                error = 1;
            }
            
            var email_id = $("#email_id").val();
            var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            $("#error-email_id").html("");
            if(email_id=="") {
                $("#error-email_id").html("Email id is required.");
                error = 1;
            }
            if(!filter.test(email_id) && email_id!="" ){
                $("#error-email_id").html("Please enter a valid email address.");
                error = 1;
            }
            
            var city = $("#city").val();
            $("#error-city").html("");
            if(city=="") {
                $("#error-city").html("City is required.");
                error = 1;
            }
            
            var quantity = $("#quantity").val();
            $("#error-quantity").html("");
            if(quantity=="") {
                $("#error-quantity").html("Quantity is required.");
                error = 1;
            }
            
            if(!error) {
                var product_id = $("#product_id").val();
                $.ajax({
                    type : "POST",
                    data : { _token: "{{ csrf_token() }}", name: name, phone: phone, email_id: email_id, city: city, quantity: quantity, product_id: product_id },
                    url  : "{{ url('product-enquiry') }}",
                    dataType : 'JSON',
                    success : function(response) {
                        $("#enquire").modal('hide');
                        toastr.success(response.message);
                    }
                });
            }
        });
        
        $(".enquirebtn").click(function() {
            $("form .error").html("");
            $("#enquire-form")[0].reset();
        });
        
        /* phone field max number 10 */
        $("#phone").keypress(function (e) {
            let myArray = [];
            for (i = 48; i < 58; i++) myArray.push(i);
            if (!(myArray.indexOf(e.which) >= 0)) e.preventDefault();
        });
    });
</script>
@endsection