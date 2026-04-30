@extends('backend.layout.main')
@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('plugins/sweetalert/sweetalert.css')}}">
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
                        <form method="POST" action="{{route('admin_tabular-offer') }}" id="pick-up-form" enctype="multipart/form-data" data-parsley-validate="">
                            {{ csrf_field() }}
                            <div id="sliders">
                                @if($slider->count())
                                    @foreach($slider as $key => $record)
                                        <div class="row" id="slider{{$key}}">
                                            @if($key > 0)
                                                <div class="col-md-12 text-end">
                                                    <div class="col-md-12 text-end"><a href="javascript:void(0)" data-db_id="{{$record->id}}" data-id="{{$key}}" class="btn btn-danger delete">Delete Below Data</a></div>
                                                    <hr/>
                                                </div>
                                            @endif
                                            <input type="hidden" name="id[{{$key}}]" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="image">{{__('Image')}}</label>
                                                <div class="profile-icon">
                                                    @php($i = $key)
                                                   
                                                    @if(isset($record->image_url))
                                                        @if(!empty($record->image_url))
                                                            <img class='previewImage img-fluid' id="uploadPreview{{$i}}" src="{{asset('uploads/tabularoffer/'.$record->image_url)}}"  alt=''>
                                                        @else
                                                            <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                                        @endif
                                                    @else
                                                        <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                                    @endif
                                                </div>
                                                <div class="m-b-10">
                                                    <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg, image/png, image/webp" @if(empty($record->image_url)) required @endif class="btn btn-warning btn-block btn-sm"  name="image[{{$key}}]"  onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                                </div>
                                                <p class="text-danger">For Best resolution please upload 50*50 size and in WebP file format.</p>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="title">{{__('Title / Alt Text')}}</label>
                                                <input type="text" class="form-control" id="title" value="{{ isset($record->title) ? $record->title : old('title') }}" name="title[{{$key}}]" placeholder="{{__('Image Title')}}" >
                                                <br>
                                                <label class="form-label" for="reorder">{{__('Reorder')}}</label>
                                                <input type="number" class="form-control" id="reorder" value="{{ isset($record->reorder) ? $record->reorder : old('reorder') }}"  name="reorder[{{$key}}]" placeholder="{{__('Reorder')}}" >
                                            </div>                                           
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="link">{{__('Button Link')}}</label>
                                                <input type="text" class="form-control" id="link" value="{{ isset($record->link) ? $record->link : old('link') }}"  name="link[{{$key}}]" placeholder="{{__('Button Link')}}">
                                                <br>
                                                <label class="form-label" for="status">{{__('Status')}}</label><br>
                                                <input type="checkbox" class="form-checkbox"   id="status" value="{{ $record->status}}" name="status[{{$key}}]" placeholder="{{__('status')}}" @if(!empty($record->status)) {{ "checked" }} @endif >
                                            </div>
                                        </div>
                                    @endforeach
                                    @php($total = $slider->count())
                                @else
                                <div class="row" id="slider0">
                                    <input type="hidden" name="id[]" value="">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="image">{{__('Image')}}</label>
                                        <div class="profile-icon">
                                        @php($i = 0)
                                            <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                        </div>
                                        <div class="m-b-10">
                                            <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg, image/png, image/webp" required class="btn btn-warning btn-block btn-sm"  name="image[]" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                        </div>
                                        <p class="text-danger">For Best resolution please upload 50*50 size and in WebP file format.</p>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="title">{{__('Title')}}</label>
                                        <input type="text" class="form-control" id="title" value="" name="title[]" placeholder="{{__('Image Title')}}" >
                                        <br>
                                        <label class="form-label" for="reorder">{{__('Reorder')}}</label>
                                        <input type="number" class="form-control" id="reorder" value="1"  name="reorder[]" placeholder="{{__('Reorder')}}" >
                                    </div>                                           
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="link">{{__('Link')}}</label>
                                        <input type="text" class="form-control" id="link" value=""  name="link[]" placeholder="{{__('Button Link')}}">
                                        <br>
                                        <label class="form-label" for="status">{{__('Status')}}</label><br>
                                        <input type="checkbox" class="form-checkbox"   id="status" value="1" name="status[]" placeholder="{{__('Status')}}">
                                    </div>
                                    @php($total = 1)
                                @endif
                            </div>
                            <input type="hidden" name="total" value="{{$total}}">
                            <input type="hidden" name="last_id" value="{{$total}}"> 
                                
                            <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>
                            <a href="{{route('admin_tabular-offer')}}" class="btn btn-danger">{{__('Cancel')}}</a>
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
                        <input type="hidden" name="id[]" value="">\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="image">Image</label>\n\
                            <div class="profile-icon">\n\
                                <img class="img-fluid" id="uploadPreview'+last_id+'" src="{{url("public/no.jpg")}}"  alt="">\n\
                            </div>\n\
                            <div class="m-b-10">\n\
                                <input type="file" id="uploadImage'+last_id+'" accept="image/x-png, image/gif, image/jpeg, image/png, image/webp" required class="btn btn-warning btn-block btn-sm"  name="image[]" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage('+last_id+');" >\n\
                            </div>\n\
                            <p class="text-danger">For Best resolution please upload 1200*500 size and in WebP file format.</p>\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="title">Title</label>\n\
                            <input type="text" class="form-control" id="title" value="" name="title[]" placeholder="Image Title">\n\
                            <br>\n\
                            <label class="form-label" for="reorder">Reorder</label>\n\
                            <input type="number" class="form-control" id="reorder" value="1"  name="reorder[]" placeholder="Reorder" >\n\
                        </div> \n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="link">Link</label>\n\
                            <input type="text" class="form-control" id="link" value=""  name="link[]" placeholder="Button Link">\n\
                            <br>\n\
                            <label class="form-label" for="status">Status</label><br>\n\
                            <input type="checkbox" class="form-checkbox"   id="status" value="1" name="status[]" placeholder="Status">\n\
                        </br>\n\
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
            text: "Are you sure? Delete this Tabular Offer!",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: true
        },
        function(){
            if(db_id){
                $.ajax({
                    url : '{{ route('admin_tabular-offer-delete') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, id : db_id},
                    success : function(result){
                        $('#slider' + id).remove();
                        var total = $('input[name="total"]').val();
                        var total = parseInt(total) - 1;
                        $('input[name="total"]').val(total);
                        window.notyf.open({
                            type : 'success',
                            message : 'Tabular Offer Deleted Successfully!',
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
                    message : 'Tabular Offer Deleted Successfully!',
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
