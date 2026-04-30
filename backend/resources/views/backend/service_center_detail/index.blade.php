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
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="col-md-12 text-end">
                            <div class="col-md-12 text-end"><a href="javascript:void(0)" id="add_more" class="btn btn-success"><i class="align-middle" data-feather="plus"></i>{{__('Add More')}}</a></div>
                        </div>
                        <form method="POST" action="{{route('admin_service-center-detail-update')}}" id="service-center-form" enctype="multipart/form-data" data-parsley-validate="">
                            {{ csrf_field() }}
                            <div id="scdetails">
                                @if($scdetail->count())
                                    @foreach($scdetail as $key => $record)
                                        <div class="row" id="scdetail{{$key}}">
                                            @if($key > 0)
                                                <div class="col-md-12 text-end">
                                                    <div class="col-md-12 text-end"><a href="javascript:void(0)" data-db_id="{{$record->id}}" data-id="{{$key}}" class="btn btn-danger delete">Delete Below Data</a></div>
                                                    <hr/>
                                                </div>
                                            @endif
                                            <input type="hidden" name="id_{{$key}}" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">
                                            <div class="col-md-6">
                                                <label class="form-label" for="name">{{__('Name')}}<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="name_{{$key}}" placeholder="{{__('Name')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->name) ? $record->name : old('name') }}">

                                                @if ($errors->has('name')) <div class="text-danger">{{ $errors->first('name') }}</div>@endif
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="address">{{__('Address')}}<span class="text-danger">*</span></label>
                                                <textarea class="form-control" name="address_{{$key}}" placeholder="{{__('Address')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->address) ? $record->address : old('address') }}">{{ isset($record->address) ? $record->address : old('address') }}</textarea>

                                                @if ($errors->has('address')) <div class="text-danger">{{ $errors->first('address') }}</div>@endif
                                            </div>
                                            <div class="mt-2 col-md-6">
                                                <label class="form-label" for="image">{{__('Image')}}</label>
                                                <input type="url" class="form-control" name="image_{{$key}}" placeholder="{{__('Image')}}" value="{{ isset($record->image) ? $record->image : old('image') }}">

                                                @if ($errors->has('image')) <div class="text-danger">{{ $errors->first('image') }}</div>@endif
                                            </div>

                                           <?php /**<div class="mt-2 col-md-6">
                                                <label for="image">{{__('Image')}}<span class="text-danger">*</span></label>
                                                <div class="image">
                                                @php($i = $key)
                                                    @if(isset($record->image))
                                                        @if($record->image !='')
                                                            @php($required = '')
                                                            <img class='previewImage img-fluid' id="uploadPreview{{$i}}" src="{{url('public/uploads/servicecenterdetail/'.$record->image)}}"  alt=''>
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
                                                    <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image_{{$key}}" {{$required}} data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});">
                                                    @if ($errors->has('image')) <div class="errors_msg">{{ $errors->first('image') }}</div>@endif
                                                </div> 
                                            </div>**/ ?>
                                            <div class="mt-2 col-md-6">
                                                <label class="form-label" for="image_title">{{__('Image Title')}}</label>
                                                <input type="text" class="form-control" name="image_title_{{$key}}" placeholder="{{__('Image Title')}}" value="{{ isset($record->image_title) ? $record->image_title : old('image_title') }}">

                                                @if ($errors->has('image_title')) <div class="text-danger">{{ $errors->first('image_title') }}</div>@endif
                                            </div>

                                            <div class="mt-2 col-md-6">
                                                <label class="form-label" for="phone_number">{{__('Phone Number')}}<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control numeric"  value="{{ isset($record->phone_number) ? $record->phone_number : old('phone_number') }}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" name="phone_number_{{$key}}" placeholder="{{__('Phone Number')}}">
                                                @if ($errors->has('phone_number')) <div class="text-danger">{{ $errors->first('phone_number') }}</div>@endif
                                            </div>
                                        </div>
                                    @endforeach
                                    @php($total = $scdetail->count())
                                @else
                                    <div class="row" id="scdetail0">
                                    <input type="hidden" name="id_0" value="">
                                        <div class="col-md-6">
                                            <label class="form-label" for="name">{{__('Name')}}<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name_0" placeholder="{{__('Name')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="">

                                            @if ($errors->has('name')) <div class="text-danger">{{ $errors->first('name') }}</div>@endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="address">{{__('Address')}}<span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="address_0" placeholder="{{__('Address')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="">{{ isset($record->address) ? $record->address : old('address') }}</textarea>

                                            @if ($errors->has('address')) <div class="text-danger">{{ $errors->first('address') }}</div>@endif
                                        </div>

                                        <?php /**<div class="mb-3 col-md-4">
                                            <label class="form-label" for="image">{{__('Image')}}<span class="text-danger">*</span></label>
                                            <div class="profile-icon">
                                            @php($i = 0)
                                            @php($required = 'required')
                                                <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                            </div>
                                            <div class="m-b-10">
                                                <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image_0"  data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                            </div>
                                        </div>**/ ?>
                                        <div class="col-md-6">
                                            <label class="form-label" for="image">{{__('Image')}}</label>
                                            <input type="text" class="form-control" name="image_0" placeholder="{{__('Image Title')}}" value="">

                                            @if ($errors->has('image')) <div class="text-danger">{{ $errors->first('image') }}</div>@endif
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="image_title">{{__('Image Title')}}</label>
                                            <input type="text" class="form-control" name="image_title_0" placeholder="{{__('Image Title')}}" value="">

                                            @if ($errors->has('image_title')) <div class="text-danger">{{ $errors->first('image_title') }}</div>@endif
                                        </div>

                                        <div class="mt-2 col-md-6">
                                            <label class="form-label" for="phone_number">{{__('Phone Number')}}<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control numeric" value="" required=""  data-parsley-required-message="{{ __("This value is required.")}}" name="phone_number_0" placeholder="{{__('Phone Number')}}">
                                            @if ($errors->has('phone_number')) <div class="text-danger">{{ $errors->first('phone_number') }}</div>@endif
                                        </div>
                                    </div>
                                    @php($total = 1)
                                @endif
                            </div>
                            <input type="hidden" name="total" value="{{$total}}">
                            <input type="hidden" name="last_id" value="{{$total}}"> 

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
    <script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
<script>
$(document).ready(function(){
    $(document).on('click', '#add_more', function(){ 
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        var total = $('input[name="total"]').val();
        var last_id = $('input[name="last_id"]').val();
        var html = '<div class="row" id="scdetail'+last_id+'">\n\
                        <div class="col-md-12 text-end">\n\
                            <div class="col-md-12 text-end"><a href="javascript:void(0)" data-db_id="0" data-id="'+last_id+'" class="btn btn-danger delete"><i class="align-middle" data-feather="trash-2"></i>Delete Below Data</a></div>\n\
                            <hr/>\n\
                        </div>\n\
                        <input type="hidden" name="id_'+last_id+'" value="">\n\
                        <div class="mb-3 col-md-6">\n\
                            <label class="form-label" for="name">Name<span class="text-danger">*</span></label>\n\
                            <input type="text" class="form-control" name="name_'+last_id+'" placeholder="Name" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="">\n\
                        </div>\n\
                        <div class="mb-3 col-md-6">\n\
                            <label class="form-label" for="address">Address<span class="text-danger">*</span></label>\n\
                            <textarea class="form-control" name="address_'+last_id+'" placeholder="Address"  required=""  data-parsley-required-message="{{ __("This value is required.")}}" value=""></textarea>\n\
                        </div>\n\
                        <div class="mb-3 col-md-6">\n\
                            <label class="form-label" for="image">Image</label>\n\
                            <input type="text" class="form-control" name="image_'+last_id+'" placeholder="Image Title" value="">\n\
                        </div>\n\
                        <div class="mb-3 col-md-6">\n\
                            <label class="form-label" for="image_title">Image Title</label>\n\
                            <input type="text" class="form-control" name="image_title_'+last_id+'" placeholder="Image Title" value="">\n\
                        </div>\n\
                        <div class="mb-3 col-md-6">\n\
                            <label class="form-label" for="phone_number">Phone Number<span class="text-danger">*</span></label>\n\
                            <input type="text" class="form-control numeric" name="phone_number_'+last_id+'" placeholder="Phone Number"  required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="">\n\
                        </div>\n\
                    </div>';
        $('#scdetails').append(html);
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
            text: "Are you sure? Delete this Service Center Detail!",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: true
        },
        function(){
            if(db_id){
                $.ajax({
                    url : '{{ route('admin_service-center-delete') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, id : db_id},
                    success : function(result){
                        $('#scdetail' + id).remove();
                        var total = $('input[name="total"]').val();
                        var total = parseInt(total) - 1;
                        $('input[name="total"]').val(total);
                        window.notyf.open({
                            type : 'success',
                            message : 'Service Center Detail Deleted Successfully!',
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
                $('#scdetail' + id).remove();
                var total = $('input[name="total"]').val();
                var total = parseInt(total) - 1;
                $('input[name="total"]').val(total);
                window.notyf.open({
                    type : 'success',
                    message : 'Service Center Deleted Successfully!',
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
