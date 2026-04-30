@extends('backend.layout.main')
@section('css')
    <link rel="stylesheet" type="text/css" href="{{asset('public/plugins/sweetalert/sweetalert.css')}}">
    <link class="js-stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}" rel="stylesheet">
@endsection
@section('content')

<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-12">
                @include('backend.alerts')
            </div>
        </div>
        <h1 class="h3 mb-3">{{$site_title}}</h1>
        <div class="row col-12">
            <div class="card">
                <div class="row">
                    <div class="col-md-4 mt-2">
                        <strong>Shop Category</strong>
                        <br>
                        <p class="text-muted">{{isset($detail->shopCategoryDetail->name) && $detail->shopCategoryDetail->name ? $detail->shopCategoryDetail->name : ''}}</p>
                    </div>
                    <div class="col-md-4 mt-2">
                        <strong>Product Name</strong>
                        <br>
                        <p class="text-muted">{{isset($detail->name) && $detail->name ? $detail->name : ''}}</p>
                    </div>
                        <div class="col-md-4 col-6 mt-2">
                            <strong>Sku</strong>
                            <br>
                            <p class="text-muted">{{isset($detail->sku) && $detail->sku ? $detail->sku : ''}}</p>
                        </div>
                    @if(isset($detail->description) && $detail->description)
                        <div class="col-md-12">
                            <strong>Description</strong>
                            <br>
                            <p class="text-muted">{!! $detail->description !!}</p>
                            <hr>
                        </div>
                    @endif
                    @if(isset($detail->specification) && $detail->specification)
                        <div class="col-md-12">
                            <strong>Specification</strong>
                            <br>
                            <p class="text-muted">{!! $detail->specification !!}</p>
                            <hr>
                        </div>
                    @endif
                    @if(isset($detail->amazon_link) && $detail->amazon_link)
                        <div class="col-md-12">
                            <strong>Amazone Link</strong>
                            <br>
                            <p class="text-muted">{{ $detail->amazon_link }}</p>
                            <hr>
                        </div>
                    @endif
                    @if(isset($detail->flipcart_link) && $detail->flipcart_link)
                        <div class="col-md-12">
                            <strong>Flipcart Link</strong>
                            <br>
                            <p class="text-muted">{{ $detail->flipcart_link }}</p>
                            <hr>
                        </div>
                    @endif
                    <div class="col-md-4 col-6 b-r">
                        <strong>Price</strong>
                        <br>
                        <p class="text-muted">{{isset($detail->price) && $detail->price ? $detail->price : ''}}</p>
                    </div>
                    <div class="col-md-4 col-6 b-r">
                        <strong>Slug</strong>
                        <br>
                        <p class="text-muted">{{isset($detail->slug) && $detail->slug ? $detail->slug : ''}}</p>
                    </div>

                    @if(isset($detail->meta_title) && $detail->meta_title || isset($detail->meta_keywords) && $detail->meta_keywords || isset($detail->meta_description) && $detail->meta_description)
                        <div class="col-md-12">
                            <h4>Seo Details</h4>
                            <hr>
                        </div>
                        @if($detail->meta_title)
                            <div class="col-md-4 col-6 b-r">
                                <strong>Meta Title</strong>
                                <br>
                                <p class="text-muted">{{isset($detail->meta_title) && $detail->meta_title ? $detail->meta_title : ''}}</p>
                            </div>
                        @endif
                        @if($detail->meta_keywords)
                            <div class="col-md-4 col-6 b-r">
                                <strong>Meta Keyword</strong>
                                <br>
                                <p class="text-muted">{{ isset($detail->meta_keywords) && $detail->meta_keywords ? $detail->meta_keywords : ''}}</p>
                            </div>
                        @endif
                        @if(isset($detail->meta_description) && $detail->meta_description)
                            <div class="col-md-4 col-6 b-r">
                                <strong>Meta Description</strong>
                                <br>
                                <p class="text-muted">{!! $detail->meta_description !!}</p>
                            </div>
                        @endif
                    @endif

                    <?php /*@if(isset($detail->primaryImage->image) && $detail->primaryImage->image) */ ?>
                        <div class="col-md-12">
                            <h4>Image</h4>
                            <hr>
                        </div>
                        <strong>Image</strong>
                        @if($images->count())
                            @foreach($images as $image)
                                <div class="col-md-3 mt-2">
                                    <img class ="img-responsive img-fluid" src="{{url('public/uploads/product/'.$image->product_id.'/'.$image->image)}}" title="{{isset($image->image_title) && $image->image_title ? $image->image_title : ''}}">
                                </div>
                            @endforeach
                        @endif
                    <?php /*@endif */?>
                </div>
            </div>
        </div>
    </div>
</main>
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection
