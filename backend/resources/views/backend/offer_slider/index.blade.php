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
                        <form method="POST" action="{{route('admin_offer-slider') }}" id="pick-up-form" enctype="multipart/form-data" data-parsley-validate="">
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
                                            <input type="hidden" name="id_{{$key}}" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="image">{{__('Image')}}</label>
                                                <div class="profile-icon">
                                                    @php($i = $key)
                                                    @if(isset($record->image))
                                                        @if($record->image !='')
                                                            <img class='previewImage img-fluid' id="uploadPreview{{$i}}" src="{{asset('uploads/offerslider/'.$record->image)}}"  alt=''>
                                                        @else
                                                            <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                                        @endif
                                                    @else
                                                        <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                                    @endif
                                                </div>
                                                <div class="m-b-10">
                                                    <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg, image/png" class="btn btn-warning btn-block btn-sm"  name="image_{{$key}}"  onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                                </div>
                                                <p class="text-danger">For Best resolution please upload 1200*500 size and in WebP file format.</p>
                                            </div>
                                            <?php /**<div class="mb-3 col-md-4">
                                                <label class="form-label" for="image">{{__('Image')}}</label>
                                                <input type="url" class="form-control" id="image" value="{{ isset($record->image) ? $record->image : old('image') }}" name="image_{{$key}}" placeholder="{{__('Image')}}" >
                                            </div>**/ ?> 
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="image_url">{{__('Image Url')}}</label>
                                                <input type="url" class="form-control"  value="{{ isset($record->image_url) ? $record->image_url : old('image_url') }}"  name="image_url_{{$key}}" placeholder="{{__('Image Url')}}">
                                               <br>
                                                <label class="form-label" for="background">{{__('Background Color')}}</label>
                                                <input type="color" class="form-control"  value="{{ !empty($record->background) ? $record->background : '#fff' }}"  name="background_{{$key}}" placeholder="{{__('Background Color')}}">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="image_title">{{__('Alt Text')}}</label>
                                                <input type="text" class="form-control" id="image_title" value="{{ isset($record->image_title) ? $record->image_title : old('image_title') }}" name="image_title_{{$key}}" placeholder="{{__('Alt Text')}}" >
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="membership_package">{{__('All Seasons Offer')}}</label><br>
                                                <input type="checkbox" class="form-checkbox"   id="membership_package" value="{{ $record->membership_package}}" name="membership_package_{{$key}}" placeholder="{{__('All Seasons Offer')}}" @if((isset($record->membership_package) && $record->membership_package=="1") || old($record->membership_package)=="1") {{ "checked" }} @endif >
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="title1">{{__('Title')}}</label>
                                                <input type="text" class="form-control" id="title1" value="{{ isset($record->title1) ? $record->title1 : old('title1') }}" name="title1_{{$key}}" placeholder="{{__('Title1')}}">
                                                <br>
                                                <label class="form-label" for="title_color">{{__('Title Color')}}</label>
                                                <input type="color" class="form-control"  value="{{ isset($record->title_color) ? $record->title_color : old('title_color') }}"  name="title_color_{{$key}}" placeholder="{{__('Title Color')}}">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="title2">{{__('Sub Title')}}</label>
                                                <input type="text" class="form-control" id="title2" value="{{ isset($record->title2) ? $record->title2 : old('title2') }}" name="title2_{{$key}}" placeholder="{{__('Title2')}}">
                                                <br>
                                                <label class="form-label" for="subtitle_color">{{__('Subtitle Color')}}</label>
                                                <input type="color" class="form-control"  value="{{ isset($record->subtitle_color) ? $record->subtitle_color : old('subtitle_color') }}"  name="subtitle_color_{{$key}}" placeholder="{{__('Subtitle Color')}}">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="btn_title">{{__('Button Title')}}</label>
                                                <input type="text" class="form-control" id="btn_title" value="{{ isset($record->btn_title) ? $record->btn_title : old('btn_title') }}"  name="btn_title_{{$key}}" placeholder="{{__('Button Title')}}">
                                            </div>   
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="btn_link">{{__('Button Link')}}</label>
                                                <input type="text" class="form-control" id="btn_link" value="{{ isset($record->btn_link) ? $record->btn_link : old('btn_link') }}"  name="btn_link_{{$key}}" placeholder="{{__('Button Link')}}">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="reorder">{{__('Reorder')}}</label>
                                                <input type="number" class="form-control" id="reorder" value="{{ isset($record->reorder) ? $record->reorder : old('reorder') }}"  name="reorder_{{$key}}" placeholder="{{__('Reorder')}}" >
                                            </div> 
                                        </div>
                                    @endforeach
                                    @php($total = $slider->count())
                                @else
                                <div class="row" id="slider0">
                                    <input type="hidden" name="id_0" value="">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="image">{{__('Image')}}</label>
                                        <div class="profile-icon">
                                        @php($i = 0)
                                            <img class='img-fluid' id="uploadPreview{{$i}}" src="{{url('public/no.jpg')}}"  alt=''>
                                        </div>
                                        <div class="m-b-10">
                                            <input type="file" id="uploadImage{{$i}}" accept="image/x-png, image/gif, image/jpeg, image/png" class="btn btn-warning btn-block btn-sm"  name="image_0" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$i}});" >
                                        </div>
                                    </div>
                                    <?php /**<div class="mb-3 col-md-4">
                                        <label class="form-label" for="image">{{__('Image')}}</label>
                                        <input type="url" class="form-control"  value="" name="image_0" placeholder="{{__('Image')}}">
                                    </div>**/ ?>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="image_url">{{__('Image Url')}}</label>
                                        <input type="url" class="form-control"  value=""  name="image_url_0" placeholder="{{__('Image Url')}}">
                                        <br>
                                        <label class="form-label" for="background">{{__('Background Color')}}</label>
                                        <input type="color" class="form-control"  value="#fff"  name="background_0" placeholder="{{__('Background Color')}}">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="image_title">{{__('Image Title')}}</label>
                                        <input type="text" class="form-control"  value="" name="image_title_0" placeholder="{{__('Image Title')}}">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="membership_package">{{__('All Seasons Offer')}}</label><br>
                                        <input type="checkbox" class="form-checkbox"  value="" name="membership_package_0" placeholder="{{__('All Seasons Offer')}}">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="title1">{{__('Title')}}</label>
                                        <input type="text" class="form-control"  value="" name="title1_0" placeholder="{{__('Title1')}}">
                                        <br>
                                        <label class="form-label" for="title_color">{{__('Title Color')}}</label>
                                        <input type="color" class="form-control"  value=""  name="title_color_0" placeholder="{{__('Title Color')}}">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="title2">{{__('Sub Title')}}</label>
                                        <input type="text" class="form-control"  value="" name="title2_0" placeholder="{{__('Title2')}}">
                                        <br>
                                        <label class="form-label" for="subtitle_color">{{__('Subtitle Color')}}</label>
                                        <input type="url" class="form-control"  value=""  name="subtitle_color_0}" placeholder="{{__('Subtitle Color')}}">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="btn_title">{{__('Button Title')}}</label>
                                        <input type="text" class="form-control"  value=""  name="btn_title_0" placeholder="{{__('Button Title')}}">
                                    </div>   
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="btn_link">{{__('Button Link')}}</label>
                                        <input type="text" class="form-control"  value=""  name="btn_link_0" placeholder="{{__('Button Link')}}">
                                    </div> 
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="reorder">{{__('Reorder')}}</label>
                                        <input type="number" class="form-control"  value=""  name="reorder_0" placeholder="{{__('Reorder')}}" >
                                </div>
                                    @php($total = 1)
                                @endif
                            </div>
                            <input type="hidden" name="total" value="{{$total}}">
                            <input type="hidden" name="last_id" value="{{$total}}"> 
                                
                            <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>
                            <a href="{{route('admin_offer-slider')}}" class="btn btn-danger">{{__('Cancel')}}</a>
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
                            <label class="form-label" for="image">Image</label>\n\
                            <div class="profile-icon">\n\
                                <img class="img-fluid" id="uploadPreview'+last_id+'" src="{{url("public/no.jpg")}}"  alt="">\n\
                            </div>\n\
                            <div class="m-b-10">\n\
                                <input type="file" id="uploadImage'+last_id+'" accept="image/x-png, image/gif, image/jpeg, image/png" class="btn btn-warning btn-block btn-sm"  name="image_'+last_id+'" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage('+last_id+');" >\n\
                            </div>\n\
                            <p class="text-danger">For Best resolution please upload 1200*500 size and in WebP file format.</p>\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="image_url">Image Url</label>\n\
                            <input type="text" class="form-control" name="image_url_'+last_id+'" placeholder="Image Url" value="">\n\
                            <br>\n\
                            <label class="form-label" for="background">Background Color</label>\n\
                            <input type="color" class="form-control"  value="#fff"  name="background_'+last_id+'" placeholder="Background Color">\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="image_title">Image Title</label>\n\
                            <input type="text" class="form-control" name="image_title_'+last_id+'" placeholder="Image Title" value="">\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="membership_package">All Seasons Offer</label><br>\n\
                            <input type="checkbox" class="form-checkbox" name="membership_package_'+last_id+'" placeholder="All Seasons Offer" value="">\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="title1">Title</label>\n\
                            <input type="text" class="form-control" name="title1_'+last_id+'" placeholder="Title" value="">\n\
                            <br>\n\
                            <label class="form-label" for="title_color">Title Color</label>\n\
                            <input type="color" class="form-control"  value=""  name="title_color_'+last_id+'" placeholder="Title Color">\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="title2">Sub Title</label>\n\
                            <input type="text" class="form-control" name="title2_'+last_id+'" placeholder="Sub Title" value="">\n\
                            <br>\n\
                            <label class="form-label" for="subtitle_color">Subtitle Color</label>\n\
                            <input type="color" class="form-control"  value=""  name="subtitle_color_'+last_id+'" placeholder="Subtitle Color">\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="btn_title">Button Title</label>\n\
                            <input type="text" class="form-control" name="btn_title_'+last_id+'" placeholder="Button Title" value="">\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="btn_link">Button Link</label>\n\
                            <input type="text" class="form-control" name="btn_link_'+last_id+'" placeholder="Button Link" value="">\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="reorder">Reorder</label>\n\
                            <input type="number" class="form-control" name="reorder_'+last_id+'" placeholder="Reorder" value="">\n\
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
            text: "Are you sure? Delete this Offer Slider!",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: true
        },
        function(){
            if(db_id){
                $.ajax({
                    url : '{{ route('admin_offer-slider-delete') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, id : db_id},
                    success : function(result){
                        $('#slider' + id).remove();
                        var total = $('input[name="total"]').val();
                        var total = parseInt(total) - 1;
                        $('input[name="total"]').val(total);
                        window.notyf.open({
                            type : 'success',
                            message : 'Offer Slider Deleted Successfully!',
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
                    message : 'Offer Slider Deleted Successfully!',
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
