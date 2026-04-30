@extends('backend.layout.main')
@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
    <style>
    .image_errortext{
        color:red;
        font-size:10px;
        white-space: nowrap;
    }
</style>
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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="@if(isset($record->id)){{ route('admin_service-category-update', array('id' => Crypt::encrypt($record->id))) }}@else{{route('admin_service-category-store')}}@endif" id="page-form" enctype="multipart/form-data" data-parsley-validate="">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="title">{{__('Title / Alt Text')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" placeholder="{{__('Title')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->title) ? $record->title : old('title') }}">
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="description">{{__('Description')}}</label>
                                    <textarea class="form-control" id="description" name="description" placeholder="{{__('Description')}}" >{{ isset($record->description) ? $record->description : old('description') }}</textarea>
                                </div>

                                <?php /**<div class="mb-3 col-md-6">
                                    <label class="form-label" for="image">{{__('Image')}}<span class="text-danger">*</span></label>
                                    <input type="url" class="form-control" id="image" name="image" placeholder="{{__('Image')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->image) ? $record->image : old('image') }}">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="image_1">{{__('Second Image')}}<span class="text-danger">*</span></label>
                                    <input type="url" class="form-control" id="image_1" name="image_1" placeholder="{{__('Second Image')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->image_1) ? $record->image_1 : old('image_1') }}">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="icon_image">{{__('Icon Image')}}<span class="text-danger">*</span></label>
                                    <input type="url" class="form-control" id="icon_image" name="icon_image" placeholder="{{__('Icon Image')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->icon_image) ? $record->icon_image : old('icon_image') }}">
                                </div>**/ ?>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="image">{{__('Image')}}</label>
                                    <div class="profile-icon">
                                        @if(isset($record->image))
                                            @if($record->image !='')
                                                @php($required = '')
                                                <img class='previewImage img-fluid' id="uploadPreview0" src="{{url('public/uploads/service/category/'.$record->image)}}"  alt=''>
                                            @else
                                                @php($required = 'required')
                                                <img class='img-fluid' id="uploadPreview0" src="{{url('public/no.jpg')}}"  alt=''>
                                            @endif
                                        @else
                                            @php($required = 'required')
                                            <img class='img-fluid' id="uploadPreview0" src="{{url('public/no.jpg')}}"  alt=''>
                                        @endif
                                    </div>
                                    <div class="m-b-10">
                                        <input type="file" id="uploadImage0" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image"  onChange="this.parentNode.nextSibling.value = this.value; PreviewImage(0);" >
                                    </div> 
                                    <p class="image_errortext">For Best resolution please upload 250*250 size and in WebP file format.</p>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="image_1">{{__('Second Image')}}</label>
                                    <div class="product_image">
                                        @if(isset($record->image_1))
                                            @if($record->image_1 !='')
                                                @php($required = '')
                                                <img class='previewImage img-fluid' id="uploadPreview1" src="{{url('public/uploads/service/category/'.$record->image_1)}}"  alt=''>
                                            @else
                                                @php($required = 'required')
                                                <img class='img-fluid' id="uploadPreview1" src="{{url('public/no.jpg')}}"  alt=''>
                                            @endif
                                        @else
                                            @php($required = 'required')
                                            <img class='img-fluid' id="uploadPreview1" src="{{url('public/no.jpg')}}"  alt=''>
                                        @endif
                                    </div>
                                    <div class="m-b-10">
                                        <input type="file" id="uploadImage1" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image_1" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage(1);">
                                    </div>
                                    <p class="image_errortext">For Best resolution please upload 250*250 size and in WebP file format.</p> 
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="icon_image">{{__('Icon Image')}}</label>
                                    <div class="icon_image">
                                        @php($i=2)
                                        @if(isset($record->icon_image))
                                            @if($record->icon_image !='')
                                                @php($required = '')
                                                <img class='previewImage img-fluid' id="uploadPreview{{$i}}" src="{{url('public/uploads/service/category/icon/'.$record->icon_image)}}"  alt=''>
                                            @else
                                                @php($required = 'required')
                                                <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                            @endif
                                        @else
                                            @php($required = 'required')
                                            <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                        @endif
                                    </div>
                                    <div class="m-b-10">
                                        <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="icon_image"  onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});">
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6">
    <label class="form-label" for="brochure">{{__('Brochure (PDF)')}}</label>
    <div class="brochure">
        @php($i=3) <!-- Change the identifier for brochure preview -->
        @if(isset($record->brochure))
            @if($record->brochure !='')
                @php($required = '')
                <a href="{{ url('public/uploads/service/category/brochures/'.$record->brochure) }}" target="_blank" class="btn btn-info">
                    View Current Brochure
                </a>
            @else
                @php($required = 'required')
                <p>No brochure uploaded</p>
            @endif
        @else
            @php($required = 'required')
            <p>No brochure uploaded</p>
        @endif
    </div>
    <div class="m-b-10">
        <input type="file" id="uploadBrochure{{$i}}" accept=".pdf" class="btn btn-warning btn-block btn-sm" name="brochure" onChange="this.parentNode.nextSibling.value = this.value;">
    </div>
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
                            </div>
                            <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>
                            <a href="{{route('admin_service-category')}}" class="btn btn-danger">Cancel</a>
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
            });
    </script>
@endsection
