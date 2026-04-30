@extends('backend.layout.main')
@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('public/plugins/sweetalert/sweetalert.css')}}">
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
                        <form method="POST" action="@if(isset($record->id)){{ route('admin_scheduled-package-update', array('id' => Crypt::encrypt($record->id))) }}@else{{route('admin_scheduled-package-store')}}@endif" id="page-form" enctype="multipart/form-data" data-parsley-validate="">
                            <input type="hidden" name="id" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="mb-3 col-md-5">
                                    <label class="form-label" for="title">{{__('Title')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" placeholder="{{__('Title')}}"  required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->title) ? $record->title : old('title') }}">
                                    @if ($errors->has('title')) <div class="text-danger">{{ $errors->first('title') }}</div>@endif
                                </div>
                                <div class="mb-3 col-md-2">
                                    <label class="form-label" for="sc_id">{{__('Category')}}<span class="text-danger">*</span></label>
                                    <select class="form-control select2" required="" name="sc_id" id="sc_id">
                                        <option value="">-- select --</option>
                                        @if($categories->count())
                                            @foreach($categories as $value)
                                                <option value="{{$value->id}}" @if(isset($record->sc_id) && $record->sc_id == $value->id) {{'selected'}} @endif>{{$value->title}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if ($errors->has('sc_id')) <div class="text-danger">{{ $errors->first('sc_id') }}</div>@endif
                                </div>
                                
                                <div class="mb-3 col-md-3">
                                    <label class="form-label" for="time_takes_option">{{__('Service Time (Hour/Day)')}}<span class="text-danger">*</span></label>
                                    <select class="form-control select2" required="" name="time_takes_option" id="time_takes_option">
                                        <option value="" @if(isset($record->time_takes_option) && $record->time_takes_option == "") {{'selected'}} @endif>{{__('Select Service Time')}}</option>
                                        <option value="Hour" @if(isset($record->time_takes_option) && $record->time_takes_option == "Hour") {{'selected'}} @endif>Hour</option>
                                        <option value="Day" @if(isset($record->time_takes_option) && $record->time_takes_option == "Day") {{'selected'}} @endif>Day</option>
                                    </select>
                                    @if ($errors->has('time_takes_option')) <div class="text-danger">{{ $errors->first('time_takes_option') }}</div>@endif
                                </div>
                                <div class="mb-3 col-md-2 @if(isset($record->time_takes_option) && $record->time_takes_option == 'Day') d-none @endif time_takes_in_hour">
                                    <label class="form-label" for="time_takes">{{__('Time Takes in Hour')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control num_only" id="time_takes" name="time_takes" placeholder="{{__('Time Takes in Hour')}}" value="{{ isset($record->time_takes) ? $record->time_takes : old('time_takes') }}">
                                    @if ($errors->has('time_takes'))
                                        <div class="text-danger">{{ $errors->first('time_takes') }}</div>
                                    @endif
                                </div>

                                <div class="mb-3 col-md-2 @if(isset($record->time_takes_option) && $record->time_takes_option == 'Hour') d-none @endif time_takes_in_day">
                                    <label class="form-label" for="time_takes_day">{{__('Time Takes in Days')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control num_only" id="time_takes_day" name="time_takes_day" placeholder="{{__('Time Takes in Days')}}" value="{{ isset($record->time_takes_day) ? $record->time_takes_day : old('time_takes_day') }}">
                                    @if ($errors->has('time_takes_day'))
                                        <div class="text-danger">{{ $errors->first('time_takes_day') }}</div>
                                    @endif
                                </div>

                                <?php /**<div class="mb-3 col-md-4">
                                    <label class="form-label" for="brand_id">{{__('Brand')}}<span class="text-danger">*</span></label>
                                    <select class="select2 form-control" name="brand_id" required="" id="brand_id">
                                        <option value="">--select--</option>
                                        @if($brands->count())
                                            @foreach($brands as $value)
                                            <option value="{{$value->id}}" {{isset($record->brand_id) && $record->brand_id == $value->id ? 'selected' : (old('brand_id') && old('brand_id') == $value->id ? 'selected' : '')}}>{{$value->title}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if ($errors->has('brand_id')) <div class="text-danger">{{ $errors->first('brand_id') }}</div>@endif
                                </div>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label" for="model_id">{{__('Model')}}<span class="text-danger">*</span></label>
                                    <select class="select2 form-control" name="model_id" required="" id="model_id">
                                        <option value="">--select--</option>
                                    </select>
                                    @if ($errors->has('model_id')) <div class="text-danger">{{ $errors->first('model_id') }}</div>@endif
                                </div>

                                <div class="mb-3 col-md-2">
                                    <label class="form-label" for="fuel_type_id">{{__('Fuel Type')}}<span class="text-danger">*</span></label>
                                    <select class="select2 form-control" name="fuel_type_id" required="" id="fuel_type_id">
                                        <option value="">--select--</option>
                                        @if($fuel_type->count())
                                            @foreach($fuel_type as $value)
                                            <option value="{{$value->id}}" {{isset($record->fuel_type_id) && $record->fuel_type_id == $value->id ? 'selected' : (old('fuel_type_id') && old('fuel_type_id') == $value->id ? 'selected' : '')}}>{{$value->title}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if ($errors->has('fuel_type_id')) <div class="text-danger">{{ $errors->first('fuel_type_id') }}</div>@endif
                                </div>

                                <div class="mb-3 col-md-2">
                                    <label class="form-label" for="price">{{__('Price')}}<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control numeric" id="price" name="price" placeholder="{{__('Price')}}"  required="" data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->price) ? $record->price : old('price') }}">
                                    @if ($errors->has('price')) <div class="text-danger">{{ $errors->first('price') }}</div>@endif
                                </div> **/ ?>

                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="warrenty_info">{{__('Warrenty Information')}}</label>
                                    <textarea class="form-control" id="warrenty_info" name="warrenty_info" placeholder="{{__('Warrenty Information')}}" data-parsley-required-message="{{ __("This value is required.")}}">{{ isset($record->warrenty_info) ? $record->warrenty_info : old('warrenty_info') }}</textarea>
                                    @if ($errors->has('warrenty_info')) <div class="text-danger">{{ $errors->first('warrenty_info') }}</div>@endif
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="recommended_info">{{__('Recommended Information')}}</label>
                                    <textarea class="form-control" id="recommended_info" name="recommended_info" placeholder="{{__('Recommended Information')}}" data-parsley-required-message="{{ __("This value is required.")}}">{{ isset($record->recommended_info) ? $record->recommended_info : old('recommended_info') }}</textarea>
                                    @if ($errors->has('recommended_info')) <div class="text-danger">{{ $errors->first('recommended_info') }}</div>@endif
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label" for="note">{{__('Note')}}</label>
                                    <input type="text" class="form-control" id="note" name="note" placeholder="{{__('Note')}}"  value="{{ isset($record->note) ? $record->note : old('note') }}">
                                    @if ($errors->has('note')) <div class="text-danger">{{ $errors->first('note') }}</div>@endif
                                    <br>
                                    <label class="form-check" for="featured_service"><input type="checkbox" id="featured_service" name="featured_service" value="" @if(!empty($record->featured)) {{ "checked" }} @endif> {{__('Featured Service')}}</label>
                                </div>
                                <?php /**<div class="mb-3 col-md-4">
                                    <label class="form-label" for="image">{{__('Image')}}</label>
                                    <input type="url" class="form-control" id="image" name="image" placeholder="{{__('Image')}}"  value="{{ isset($record->image) ? $record->image : old('image') }}" required="" data-parsley-required-message="{{ __("This value is required.")}}">
                                    @if ($errors->has('image')) <div class="text-danger">{{ $errors->first('image') }}</div>@endif
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label" for="image_other">{{__('Other Image')}}</label>
                                    <input type="url" class="form-control" id="image_other" name="image_other" placeholder="{{__('Image')}}"  value="{{ isset($record->image_other) ? $record->image_other : old('image_other') }}">
                                    @if ($errors->has('image_other')) <div class="text-danger">{{ $errors->first('image_other') }}</div>@endif
                                </div>**/ ?>

                                <div class="mb-3 col-md-4">
                                    <label class="form-label" for="image">{{__('Image')}}</label>
                                    <div class="profile-icon">
                                        @php($i = 0)
                                        @if(isset($record->image))
                                            @if($record->image !='')
                                                <img class='img-responsive previewImage img-fluid' id="uploadPreview{{$i}}" src="{{url('public/uploads/service/package/'.$record->image)}}"  alt=''>
                                            @else
                                                <img class='img-responsive img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                            @endif
                                        @else
                                            <img class='img-responsive img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                        @endif
                                    </div>
                                    <div class="m-b-10">
                                        <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image" data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                        @if ($errors->has('image')) <div class="errors_msg">{{ $errors->first('image') }}</div>@endif
                                    </div>
                                    <small class="text-danger">Please upload image size : 403*302 px</small>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label" for="image2">{{__('Other Image')}}</label>
                                    <div class="profile-icon">
                                        @php($i = $i + 1)
                                        @if(isset($record->image_other))
                                            @if($record->image_other !='')
                                                <img class='img-responsive previewImage img-fluid' id="uploadPreview{{$i}}" src="{{url('public/uploads/service/package/'.$record->image_other)}}"  alt=''>
                                            @else
                                                <img class='img-responsive img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                            @endif
                                        @else
                                            <img class='img-responsive img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                        @endif
                                    </div>
                                    <div class="m-b-10">
                                        <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image_other" data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                        @if ($errors->has('image_other')) <div class="errors_msg">{{ $errors->first('image_other') }}</div>@endif
                                    </div>
                                    <small class="text-danger">Please upload image size : 403*302 px</small>
                                </div>

                                <div class="col-12 row" id="specifications">
                                    <div class="col-6">
                                        <h6>Specification</h6>
                                    </div>
                                    <div class="col-md-6 mb-3 text-end">
                                        <a href="javascript:void(0)" id="add_more" class="btn btn-success">
                                            <i class="align-middle" data-feather="plus"></i>
                                            {{__('Add More')}}
                                        </a>
                                    </div>
                                    <hr/>

                                    @if(isset($specifications) && $specifications->count())
                                        @php($total = $specifications->count())
                                        @foreach($specifications as $skey => $sval)
                                            <div id="specification{{$skey}}">
                                                <div class="col-md-12 text-end">
                                                    <a href="javascript:void(0)" data-db_id="{{ isset($sval->id) ? $sval->id : '' }}" data-id="{{$skey}}" class="mb-3 btn btn-danger delete"><i class="align-middle" data-feather="trash-2"></i>Delete Below Data</a>
                                                </div>
                                                <input type="hidden" name="sid[]" value="{{ isset($sval->id) ? $sval->id : '' }}">
                                                <div class="mb-3 col-md-12">
                                                    <input type="text" class="form-control" id="specification" name="specification[]" placeholder="{{__('Specification')}}" value="{{ isset($sval->specification) ? $sval->specification : '' }}"  required=""  data-parsley-required-message="{{ __("This value is required.")}}">
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        @php($total = 1)
                                        <div class="mb-3 col-md-12" id="specification0">
                                            <input type="hidden" name="sid[]" value="">
                                            <input type="text" class="form-control" id="specification" name="specification[]" placeholder="{{__('Specification')}}"  required=""  data-parsley-required-message="{{ __("This value is required.")}}">
                                        </div>
                                    @endif
                                </div>
                                <input type="hidden" name="total" value="{{$total}}">
                                <input type="hidden" name="last_id" value="{{$total}}"> 

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
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>
                            <a href="{{route('admin_scheduled-package')}}" class="btn btn-danger">{{__('Cancel')}}</a>
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
    <script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
    <script src="{{asset('public/plugins/ckeditor/ckeditor.js')}}"  type="text/javascript"></script>
    <script>
        $(document).ready(function(){
            $('.select2').select2();
            var brand = "{{ isset($record->brand_id) && $record->brand_id ? $record->brand_id : old('brand_id') }}";
            var cmodel = "{{ isset($record->model_id) && $record->model_id ? $record->model_id : old('model_id') }}";
            getModelFromBrand(brand, cmodel);
            $(document).on('change', '#brand_id', function(){
                var brand = $(this).val();
                getModelFromBrand(brand);
            });
            $(document).on('click', '#add_more', function(){
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                var total = $('input[name="total"]').val();
                var last_id = $('input[name="last_id"]').val();
                var html = '<div id="specification'+last_id+'">\n\
                                <div class="col-md-12 text-end">\n\
                                    <a href="javascript:void(0)" data-db_id="0" data-id="'+last_id+'" class="mb-3 btn btn-danger delete"><i class="align-middle" data-feather="trash-2"></i>Delete Below Data</a>\n\
                                </div>\n\
                                <input type="hidden" name="sid[]" value="">\n\
                                <div class="mb-3 col-md-12">\n\
                                    <input type="text" class="form-control" id="button_text" name="specification[]" placeholder="Specification" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="">\n\
                                </div>\n\
                            </div>';
                $('#specifications').append(html);
                var total = parseInt(total) + 1;
                $('input[name="total"]').val(total);
                var lastId = parseInt(last_id) + 1;
                $('input[name="last_id"]').val(lastId);
            });

            $(document).on('click', '.delete', function(){
                var db_id = $(this).data('db_id');
                var id = $(this).data('id');
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                swal({
                    title: "",
                    text: "Are you sure? Delete this Specification!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, delete it!",
                    closeOnConfirm: true
                },
                function(){
                    if(db_id){
                        $.ajax({
                            url : '{{ route('admin_specification-delete') }}',
                            method : 'post',
                            data : {_token: CSRF_TOKEN, id : db_id},
                            success : function(result){
                                $('#specification' + id).remove();
                                var total = $('input[name="total"]').val();
                                var total = parseInt(total) - 1;
                                $('input[name="total"]').val(total);
                                window.notyf.open({
                                    type : 'success',
                                    message : 'Specification Deleted Successfully!',
                                    duration : '10000',
                                    ripple : true,
                                    dismissible : true,
                                    position: {
                                            x: 'right',
                                            y: 'top'
                                    }
                                });
                            }
                        });
                    } else {
                        $('#specification' + id).remove();
                        var total = $('input[name="total"]').val();
                        var total = parseInt(total) - 1;
                        $('input[name="total"]').val(total);
                        window.notyf.open({
                            type : 'success',
                            message : 'Specification Deleted Successfully!',
                            duration : '10000',
                            ripple : true,
                            dismissible : true,
                            position: {
                                    x: 'right',
                                    y: 'top'
                            }
                        });
                    }
                });
            });
            
            $("#time_takes_option").change(function() {
                var time_takes_option = $(this).val();
                $(".time_takes_in_hour").addClass("d-none");
                $(".time_takes_in_day").addClass("d-none");
                $(".time_takes_in_hour #time_takes").attr("required", false);
                $(".time_takes_in_day #time_takes_day").attr("required", false);
                if(time_takes_option=="Hour") {
                    $(".time_takes_in_hour").removeClass("d-none");
                    $(".time_takes_in_day").addClass("d-none");
                    $(".time_takes_in_hour #time_takes").attr("required", true);
                }
                if(time_takes_option=="Day") {
                    $(".time_takes_in_hour").addClass("d-none");
                    $(".time_takes_in_day").removeClass("d-none");
                    $(".time_takes_in_day #time_takes_day").attr("required", true);
                }
            });

            function getModelFromBrand(brand = '', cmodel = ''){
                if(brand != '' || brand != null){
                    var cmodel = "{{isset($record->model_id) && $record->model_id ? $record->model_id : old('model_id')}}";
                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url : '{{ route('admin_get-model-from-brand') }}',
                        method : 'post',
                        data : {_token: CSRF_TOKEN, brand_id : brand},
                        success : function(result){
                            var result = $.parseJSON(result);
                            $('#model_id').html(result.html).trigger('change');
                            if(cmodel){
                                $('#model_id').val(cmodel).trigger('change');
                            }
                        }
                    });
                }
            }
        });
    </script>
@endsection