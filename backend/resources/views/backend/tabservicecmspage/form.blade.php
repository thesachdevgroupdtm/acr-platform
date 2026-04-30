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

            <div class="col-md-12">

                <div class="card">

                    <div class="card-body">

                        <form method="POST" action="@if(isset($record->id)){{ route('admin_tabservicecms-update', array('id' => Crypt::encrypt($record->id))) }}@else{{route('admin_tabservicecms-store')}}@endif" id="page-form" enctype="multipart/form-data" data-parsley-validate="">

                            <input type="hidden" name="id" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">

                            {{ csrf_field() }}

                            <div class="row">

                                <div class="mb-3 col-md-4">

                                    <label class="form-label" for="section">{{__('Section')}}<span class="text-danger"></span></label>

                                    <select class="form-control select2" id="section" name="section">

                                        <option value="">-- select --</option>

                                        <option value="0" @if(isset($record->section) && $record->section == '0'){{'selected'}}@endif>Second</option>

                                        <option value="1" @if(isset($record->section) && $record->section == '1'){{'selected'}}@endif>Third</option>

                                        <option value="2" @if(isset($record->section) && $record->section == '2'){{'selected'}}@endif>Forth</option>

                                        <option value="3" @if(isset($record->section) && $record->section == '3'){{'selected'}}@endif>None</option>

                                    </select>

                                </div>

                                <div class="mb-3 col-md-4">

                                    <label class="form-label" for="name">{{__('Page Name')}}<span class="text-danger">*</span></label>

                                    <input type="text" class="form-control" id="name" name="name" placeholder="{{__('Page Name')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->name) ? $record->name : old('name') }}">



                                    @if ($errors->has('name')) <div class="text-danger">{{ $errors->first('name') }}</div>@endif

                                </div>



                                <div class="mb-3 col-md-4">

                                    <label class="form-label" for="slug">{{__('Slug')}}<span class="text-danger">*</span></label>

                                    <input type="text" class="form-control" id="slug" name="slug" placeholder="{{__('Slug')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}"  value="{{ isset($record->slug) ? $record->slug : old('slug') }}">



                                    @if ($errors->has('slug')) <div class="text-danger">{{ $errors->first('slug') }}</div>@endif

                                </div>



                                <div class="mb-3 col-md-12">

                                    <label class="form-label" for="description">{{__('Description')}}<span class="text-danger">*</span></label>

                                    <textarea class="form-control" id="description" name="description" placeholder="{{__('Description')}}">{{ isset($record->description) ? $record->description : old('description') }}</textarea>

                                    @if ($errors->has('description')) <div class="text-danger">{{ $errors->first('description') }}</div>@endif

                                </div>

                                <div class="mb-3 col-md-6">

                                    <label class="form-label" for="banner_image">{{__('Image')}}<span class="text-danger">*</span></label>

                                    <div class="profile-icon">

                                        @php($i = 0)

                                        @if(isset($record->banner_image))

                                            @if($record->banner_image !='')

                                                @php($required = '')

                                                <img style="width:15%;" class='img-responsive previewImage img-fluid' id="uploadPreview{{$i}}" src="{{asset('uploads/compnycms/'.$record->banner_image)}}"  alt=''>

                                            @else

                                                @php($required = 'required')

                                                <img style="width:15%;" class='img-responsive img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>

                                            @endif

                                        @else

                                            @php($required = 'required')

                                            <img style="width:15%;" class='img-responsive img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>

                                        @endif

                                    </div>

                                    <div class="m-b-10">

                                        <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="banner_image" {{$required}} data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >

                                        @if ($errors->has('banner_image')) <div class="errors_msg">{{ $errors->first('banner_image') }}</div>@endif

                                    </div>

                                </div>

                                <div class="mb-3 col-md-6">

                                    <label class="form-label" for="image_title">{{__('Banner Image Title')}}<span class="text-danger">*</span></label>

                                    <input type="text" class="form-control" id="image_title" name="image_title" placeholder="{{__('Image Title')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}"  value="{{ isset($record->image_title) ? $record->image_title : old('image_title') }}">



                                    @if ($errors->has('image_title')) <div class="text-danger">{{ $errors->first('image_title') }}</div>@endif

                                </div>

                                <div class="mb-3 col-md-6">

                                    <label class="form-label" for="banner_text">{{__('Banner Text')}}<span class="text-danger">*</span></label>

                                    <input type="text" class="form-control" id="banner_text" name="banner_text" placeholder="{{__('Banner Text')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}"  value="{{ isset($record->banner_text) ? $record->banner_text : old('banner_text') }}">



                                    @if ($errors->has('banner_text')) <div class="text-danger">{{ $errors->first('banner_text') }}</div>@endif

                                </div>

                                <div class="mb-3 col-md-6">

                                    <label class="form-label" for="meta_title">{{__('Meta Title')}}</label>

                                    <input type="text" class="form-control" id="meta_title" name="meta_title" placeholder="{{__('Meta Title')}}" value="{{ isset($record->meta_title) ? $record->meta_title : old('meta_title') }}">

                                    @if ($errors->has('meta_title')) <div class="text-danger">{{ $errors->first('meta_title') }}</div>@endif

                                </div>
                                

                                <div class="mb-3 col-md-12">

                                    <label class="form-label" for="extra_meta_tag">{{__('Extra Meta Tag')}}</label>

                                    <textarea class="form-control" id="extra_meta_tag" name="extra_meta_tag" placeholder="{{__('Extra Meta Tag')}}">{{ isset($record->extra_meta_tag) ? $record->extra_meta_tag : old('extra_meta_tag') }}</textarea>

                                    @if ($errors->has('extra_meta_tag')) <div class="text-danger">{{ $errors->first('extra_meta_tag') }}</div>@endif

                                </div>

<div class="mb-3 col-md-6">
    <label class="form-label" for="brochure">{{__('Brochure Upload')}}</label>
    <div class="profile-icon">
        @if(isset($record->brochure) && $record->brochure !='')
            <a href="{{ asset('uploads/brochures/'.$record->brochure) }}" target="_blank">View Uploaded Brochure</a>
        @endif
    </div>
    <input type="file" id="brochure" class="form-control" name="brochure" accept=".pdf,.doc,.docx">
    @if ($errors->has('brochure')) <div class="text-danger">{{ $errors->first('brochure') }}</div>@endif
</div>


                                <div class="mb-3 col-md-6">

                                    <label class="form-label" for="meta_keywords">{{__('Meta Keyword')}}</label>

                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" placeholder="{{__('Meta Keyword')}}" value="{{ isset($record->meta_keywords) ? $record->meta_keywords : old('meta_keywords') }}">

                                    @if ($errors->has('meta_keywords')) <div class="text-danger">{{ $errors->first('meta_keywords') }}</div>@endif

                                </div>

                                <div class="mb-3 col-md-6">

                                    <label class="form-label" for="meta_description">{{__('Meta Description')}}</label>

                                    <input type="text" class="form-control" id="meta_description" name="meta_description" placeholder="{{__('Meta Description')}}" value="{{ isset($record->meta_description) ? $record->meta_description : old('meta_description') }}">

                                    @if ($errors->has('meta_description')) <div class="text-danger">{{ $errors->first('meta_description') }}</div>@endif

                                </div>

                                <div class="mb-3 col-md-4">

                                    <label class="form-label" for="canonical_tag">{{__('Canonical Tag')}}</label>

                                    <input type="text" class="form-control" id="canonical_tag" name="canonical_tag" placeholder="{{__('Canonical Tag')}}" value="{{ isset($record->canonical_tag) ? $record->canonical_tag : old('canonical_tag') }}">

                                    @if ($errors->has('canonical_tag')) <div class="text-danger">{{ $errors->first('canonical_tag') }}</div>@endif

                                </div>

                            </div>



                            <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>

                            <a href="{{route('admin_tabservicecms')}}" class="btn btn-danger">{{__('Cancel')}}</a>

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

            $('#section').select2();

            CKEDITOR.replace('description', {

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