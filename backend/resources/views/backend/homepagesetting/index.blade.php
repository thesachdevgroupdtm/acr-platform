@extends('backend.layout.main')
@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
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
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{route('admin_home-page-content-update')}}" id="faq-form" enctype="multipart/form-data" data-parsley-validate="">
                            <input type="hidden" name="id" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label" for="section1_title1">{{__('Section 1 Title 1')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="section1_title1" name="section1_title1" placeholder="{{__('Section 1 Title 1')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->section1_title1) ? $record->section1_title1 : old('section1_title1') }}">

                                    @if ($errors->has('section1_title1')) <div class="text-danger">{{ $errors->first('section1_title1') }}</div>@endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="section1_title2">{{__('Section 1 Title 2')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="section1_title2" name="section1_title2" placeholder="{{__('Section 1 Title 2')}}"  value="{{ isset($record->section1_title2) ? $record->section1_title2 : old('section1_title2') }}">

                                    @if ($errors->has('section1_title2')) <div class="text-danger">{{ $errors->first('section1_title2') }}</div>@endif
                                </div>

                                <div class="mt-2 col-md-6">
                                    <label for="section1_image">{{__('Section 1 Image')}}</label>
                                    <div class="col-md-12 text-end">
                                        <a href="javascript:void(0)" id="add_more_slider" class="btn btn-success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus align-middle"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Add Slider Image</a>
                                    </div>
                                    <div class="home_slider_image">
                                    @php($required = 'required')
                                        <?php
                                        $sliderImages=!empty($record->section1_image)?json_decode($record->section1_image):'';
                                        ?>
                                        @if(!empty($sliderImages))
                                 
                                            @foreach ($sliderImages as $index=>$image )  
                                            @php($required = '')
                                            <div class="slider-image">
    
                                                <img class='previewImage' src="{{asset('uploads/content/'.$image)}}"  alt=''>
                                                <input type="file" accept="image/x-png, image/gif, image/jpeg, image/png, image/webp, image/jpg" class="btn btn-warning btn-block btn-sm"  name="section1_image[]" onChange="displaySelectedFile(this);">
                                                <input type="hidden" name="sliders[]" value="{{$index}}" />
                                                <div class="delete-slider"><i class="fa fa-trash" aria-hidden="true"></i></div>
                                            </div>
                                            @endforeach
                                        @else
                                            @php($required = 'required')
                                            <div class="slider-image">
                                                <img class='previewImage' src="{{asset('uploads/no.jpg')}}"  alt=''>
                                                <input type="file" accept="image/x-png, image/gif, image/jpeg, image/png, image/webp, image/jpg" class="btn btn-warning btn-block btn-sm"  name="section1_image[]" onChange="displaySelectedFile(this);">
                                                <div class="delete-slider"><i class="fa fa-trash" aria-hidden="true"></i></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <?php /**<div class="col-md-6">
                                    <label class="form-label" for="section1_image">{{('Section 1 Image')}}</label>
                                    <input type="text" class="form-control" id="section1_image" name="section1_image" placeholder="{{__('Section 1 Image')}}"  value="{{ isset($record->section1_image) ? $record->section1_image : old('section1_image') }}">

                                    @if ($errors->has('image_title')) <div class="text-danger">{{ $errors->first('image_title') }}</div>@endif
                                </div>**/ ?>

                                <div class="col-md-6">
                                    <label class="form-label" for="image_title">{{__('Image Title')}}</label>
                                    <input type="text" class="form-control" id="image_title" name="image_title" placeholder="{{__('Image Title')}}"  value="{{ isset($record->image_title) ? $record->image_title : old('image_title') }}">

                                    @if ($errors->has('image_title')) <div class="text-danger">{{ $errors->first('image_title') }}</div>@endif
                                </div>

                                <div class="mt-2 col-md-6">
                                    <label class="form-label" for="section1_description">{{__('Section1 Description')}}</label>
                                    <textarea class="form-control" id="section1_description" name="section1_description" placeholder="{{__('Section1 Description')}}">{{ isset($record->section1_description) ? $record->section1_description : old('section1_description') }}</textarea>
                                    @if ($errors->has('section1_description')) <div class="text-danger">{{ $errors->first('section1_description') }}</div>@endif
                                </div>

                                <div class="mt-2 col-md-6">
                                    <label class="form-label" for="footer_description">{{__('Footer Description')}}</label>
                                    <textarea class="form-control" id="footer_description" name="footer_description" placeholder="{{__('Footer Description')}}">{{ isset($record->footer_description) ? $record->footer_description : old('footer_description') }}</textarea>
                                    @if ($errors->has('footer_description')) <div class="text-danger">{{ $errors->first('footer_description') }}</div>@endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="button_title">{{__('Button Title')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="button_title" name="button_title" placeholder="{{__('Button Title')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->button_title) ? $record->button_title : old('button_title') }}">

                                    @if ($errors->has('button_title')) <div class="text-danger">{{ $errors->first('button_title') }}</div>@endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="button_link">{{__('Button link')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="button_link" name="button_link" placeholder="{{__('Button Link')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->button_link) ? $record->button_link : old('button_link') }}">

                                    @if ($errors->has('button_link')) <div class="text-danger">{{ $errors->first('button_link') }}</div>@endif
                                </div>
                                <div class="mt-3  col-md-12">
                                    <hr/>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3 col-md-12">
                                        <label class="form-label" for="price_list">{{__('Price List')}}</label>
                                        <textarea class="form-control" id="price_list" name="price_list" placeholder="{{__('Price List')}}">{{ isset($record->price_list) ? $record->price_list : old('price_list') }}</textarea>
                                        @if ($errors->has('price_list')) <div class="text-danger">{{ $errors->first('price_list') }}</div>@endif
                                    </div>
                                </div>

                                <div class="mt-3  col-md-12">
                                    <h6>SEO Details</h6>
                                    <hr/>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="meta_title">{{__('Meta Title')}}</label>
                                        <input type="text" class="form-control" id="meta_title" name="meta_title" placeholder="{{__('Meta Title')}}" value="{{ isset($record->meta_title) ? $record->meta_title : old('meta_title') }}">
                                        @if ($errors->has('meta_title')) <div class="text-danger">{{ $errors->first('meta_title') }}</div>@endif
                                    </div>
                                    
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="meta_keywords">{{__('Meta Keyword')}}</label>
                                        <textarea class="form-control" id="meta_keywords" name="meta_keywords" placeholder="{{__('Meta Keyword')}}">{{ isset($record->meta_keywords) ? $record->meta_keywords : old('meta_keywords') }}</textarea>
                                        @if ($errors->has('meta_keywords')) <div class="text-danger">{{ $errors->first('meta_keywords') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="meta_description">{{__('Meta Description')}}</label>
                                        <textarea class="form-control" id="meta_description" name="meta_description" placeholder="{{__('Meta Description')}}">{{ isset($record->meta_description) ? $record->meta_description : old('meta_description') }}</textarea>
                                        @if ($errors->has('meta_description')) <div class="text-danger">{{ $errors->first('meta_description') }}</div>@endif
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="canonical_tag">{{__('Canonical Tag')}}</label>
                                        <input type="text" class="form-control" id="canonical_tag" name="canonical_tag" placeholder="{{__('Canonical Tag')}}" value="{{ isset($record->canonical_tag) ? $record->canonical_tag : old('canonical_tag') }}">
                                        @if ($errors->has('canonical_tag')) <div class="text-danger">{{ $errors->first('canonical_tag') }}</div>@endif
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-md-12">
                                    <label class="form-label" for="extra_meta_tag">{{__('Extra Meta Tag')}}</label>

                                    <textarea class="form-control"  name="extra_meta_tag" placeholder="{{__('Extra Meta Tag')}}">{{ isset($record->extra_meta_tag) ? $record->extra_meta_tag : old('extra_meta_tag') }}</textarea>
                                    @if ($errors->has('extra_meta_tag')) <div class="text-danger">{{ $errors->first('extra_meta_tag') }}</div>@endif
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-2">{{__('Submit')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection
@section('javascript')
    <script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
    <script src="{{asset('public/plugins/ckeditor/ckeditor.js')}}"  type="text/javascript"></script>
    <script>
        $(document).ready(function(){
            CKEDITOR.replace('price_list', {
                height:1000,
                removePlugins : 'resize',
                filebrowserBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=files") ?>',
                filebrowserImageBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=images") ?>',
                filebrowserFlashBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=flash") ?>',
                filebrowserUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=files") ?>',
                filebrowserImageUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=images") ?>',
                filebrowserFlashUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=flash") ?>',
            });
            CKEDITOR.on('instanceReady', function () {
                $('#description').attr('required', '');
                $.each(CKEDITOR.instances, function (instance) {
                    CKEDITOR.instances[instance].on("change", function (e) {
                        for (instance in CKEDITOR.instances) {
                            CKEDITOR.instances[instance].updateElement();
                            //$('form').parsley().validate();
                        }
                    });
                });
            });

            $(".home_slider_image").on("click",".delete-slider",function(){
                $(this).closest('.slider-image').remove();
            });
        });
    </script>
@endsection
