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
                        <div class="col-md-12 text-end">
                            <div class="col-md-12 text-end"><a href="javascript:void(0)" id="add_more" class="btn btn-success"><i class="align-middle" data-feather="plus"></i>{{__('Add More')}}</a></div>
                        </div>
                        <form method="POST" action="{{route('admin_brand-logo-slider') }}" id="pick-up-form" enctype="multipart/form-data" data-parsley-validate="">
                            {{ csrf_field() }}
                            <div id="sliders">
                                @if($brandslider->count())
                                    @foreach($brandslider as $key => $record)
                                        <div class="row" id="slider{{$key}}">
                                            @if($key > 0)
                                                <div class="col-md-12 text-end">
                                                    <div class="col-md-12 text-end"><a href="javascript:void(0)" data-db_id="{{$record->id}}" data-id="{{$key}}" class="btn btn-danger delete">Delete Below Data</a></div>
                                                    <hr/>
                                                </div>
                                            @endif
                                            <input type="hidden" name="id_{{$key}}" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="image">{{__('Image')}}<span class="text-danger">*</span></label>
                                                <div class="profile-icon">
                                                    @php($i = $key)
                                                    @if(isset($record->image))
                                                        @if($record->image !='')
                                                            @php($required = '')
                                                            <img class='previewImage img-fluid' id="uploadPreview{{$i}}" src="{{asset('uploads/brandlogoslider/'.$record->image)}}"  alt=''>
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
                                                    <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image_{{$key}}" {{$required}} data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                                </div>
                                                <p class="image_errortext">For Best resolution please upload 1280*444 size and in WebP file format.</p>
                                            </div>
                                            <?php /**<div class="mb-3 col-md-4">
                                                <label class="form-label" for="image">{{__('Image')}}</label>
                                                <input type="text" class="form-control" value="{{ isset($record->image) ? $record->image : old('image') }}" name="image_{{$key}}" placeholder="{{__('Image')}}" >
                                            </div>**/ ?>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="image_title">{{__('Image Title / Alt Text')}}</label>
                                                <input type="text" class="form-control" value="{{ isset($record->image_title) ? $record->image_title : old('image_title') }}" name="image_title_{{$key}}" placeholder="{{__('Image Title')}}" >
                                            </div>
                                        </div>
                                    @endforeach
                                    @php($total = $brandslider->count())
                                @else
                               <div class="row" id="slider0">
                                    <input type="hidden" name="id_0" value="">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="image">{{__('Image')}}<span class="text-danger">*</span></label>
                                        <div class="profile-icon">
                                        @php($i = 0)
                                        @php($required = 'required')
                                            <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                        </div>
                                        <div class="m-b-10">
                                            <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image_0"  data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                        </div>
                                    </div>
                                </div>
                                 <?php /**<div class="mb-3 col-md-4">
                                    <label class="form-label" for="image">{{__('Image')}}</label>
                                    <input type="text" class="form-control" value="{{ isset($record->image) ? $record->image : old('image') }}" name="image_0" placeholder="{{__('Image')}}" >
                                </div>**/ ?>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label" for="image_title">{{__('Image Title')}}</label>
                                    <input type="text" class="form-control" value="{{ isset($record->image_title) ? $record->image_title : old('image_title') }}" name="image_title_0" placeholder="{{__('Image Title')}}" >
                                </div>
                                    @php($total = 1)
                                @endif
                            </div>
                            <input type="hidden" name="total" value="{{$total}}">
                            <input type="hidden" name="last_id" value="{{$total}}"> 

                            <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>
                            <a href="{{route('admin_brand-logo-slider')}}" class="btn btn-danger">{{__('Cancel')}}</a>
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
<script>
$(document).ready(function(){
    $(document).on('click', '#add_more', function(){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        var total = $('input[name="total"]').val();
        var last_id = $('input[name="last_id"]').val();
        var html = '<div class="row" id="slider'+last_id+'">\n\
                        <div class="col-md-12 text-end">\n\
                            <div class="col-md-12 text-end"><a href="javascript:void(0)" data-db_id="0" data-id="'+last_id+'" class="btn btn-danger delete"><i class="align-middle" data-feather="trash-2"></i>Delete Below Data</a></div>\n\
                            <hr/>\n\
                        </div>\n\
                        <input type="hidden" name="id_'+last_id+'" value="">\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="image">Image<span class="text-danger">*</span></label>\n\
                            <div class="profile-icon">\n\
                                <img class="img-fluid" id="uploadPreview'+last_id+'" src="{{url("public/no.jpg")}}"  alt="">\n\
                            </div>\n\
                            <div class="m-b-10">\n\
                                <input type="file" id="uploadImage'+last_id+'" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image_'+last_id+'" required data-parsley-required-message="This value is required." onChange="this.parentNode.nextSibling.value = this.value; PreviewImage('+last_id+');" >\n\
                            </div>\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="image_title">Image Title</label>\n\
                            <input type="text" class="form-control" name="image_title_'+last_id+'" placeholder="Image Title" value="">\n\
                        </div>\n\
                    </div>';
        $('#sliders').append(html);
        var total = parseInt(total) + 1;
        $('input[name="total"]').val(total);
        var lastId = parseInt(last_id) + 1;
        $('input[name="last_id"]').val(lastId);
        basic();
    });

    $(document).on('click', '.delete', function(){
        var db_id = $(this).data('db_id');
        var id = $(this).data('id');
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        swal({
            title: "",
            text: "Are you sure? Delete this Brand Logo Slider!",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: true
        },
        function(){
            if(db_id){
                $.ajax({
                    url : '{{ route('admin_brand-logo-slider-delete') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, id : db_id},
                    success : function(result){
                        $('#slider' + id).remove();
                        var total = $('input[name="total"]').val();
                        var total = parseInt(total) - 1;
                        $('input[name="total"]').val(total);
                        window.notyf.open({
                            type : 'success',
                            message : 'Brand Logo Slider Deleted Successfully!',
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
                $('#slider' + id).remove();
                var total = $('input[name="total"]').val();
                var total = parseInt(total) - 1;
                $('input[name="total"]').val(total);
                window.notyf.open({
                    type : 'success',
                    message : 'Brand Logo Slider Deleted Successfully!',
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
});
</script>
@endsection
