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
                        <form method="POST" action="{{route('admin_pick-up-slot-settings') }}" id="pick-up-form" enctype="multipart/form-data" data-parsley-validate="">
                            {{ csrf_field() }}
                            <div id="sliders">
                                @if($slots->count())
                                    @foreach($slots as $key => $record)
                                        <div class="row" id="slider{{$key}}">
                                            @if($key > 0)
                                                <div class="col-md-12 text-end">
                                                    <div class="col-md-12 text-end"><a href="javascript:void(0)" data-db_id="{{$record->id}}" data-id="{{$key}}" class="btn btn-danger delete">Delete Below Data</a></div>
                                                    <hr/>
                                                </div>
                                            @endif
                                            <input type="hidden" name="id_{{$key}}" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="time">{{__('Time')}}<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="time_{{$key}}" placeholder="{{__('Time')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->time) ? $record->time : old('time') }}">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="slot">{{__('Slot')}}<span class="text-danger">*</span></label>
                                                <select class="form-control" name="slot_{{$key}}" required="">
                                                    <option value="">-- select --</option>
                                                    <option value="1" @if(isset($record->slot) && $record->slot == '1'){{'selected'}}@endif>Afternoon</option>
                                                    <option value="0" @if(isset($record->slot) && $record->slot == '0'){{'selected'}}@endif>Evening</option>
                                                    <option value="2" @if(isset($record->slot) && $record->slot == '2'){{'selected'}}@endif>Morning</option>
                                                </select>
                                            </div>
                                        </div>
                                    @endforeach
                                    @php($total = $slots->count())
                                @else
                                    <div class="row" id="slider0">
                                        <input type="hidden" name="id_0" value="">
                                        <div class="mb-3 col-md-4">
                                            <label class="form-label" for="time">{{__('Time')}}<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="time_0" placeholder="{{__('Time')}}"  required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="">
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label class="form-label" for="slot">{{__('Slot')}}<span class="text-danger">*</span></label>
                                            <select class="form-control" name="slot_0" required="">
                                                <option value="">-- select --</option>
                                                <option value="1">Afternoon</option>
                                                <option value="0">Evening</option>
                                                <option value="2">Morning</option>
                                            </select>
                                        </div>
                                    </div>
                                    @php($total = 1)
                                @endif
                            </div>
                            <input type="hidden" name="total" value="{{$total}}">
                            <input type="hidden" name="last_id" value="{{$total}}"> 

                            <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>
                            <a href="{{route('admin_pick-up-slot-settings')}}" class="btn btn-danger">{{__('Cancel')}}</a>
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
                            <label class="form-label" for="time">Time<span class="text-danger">*</span></label>\n\
                            <input type="text" class="form-control" name="time_'+last_id+'" placeholder="Time"  required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="">\n\
                        </div>\n\
                        <div class="mb-3 col-md-4">\n\
                            <label class="form-label" for="slot">Slot<span class="text-danger">*</span></label>\n\
                            <select class="form-control" name="slot_'+last_id+'" required="">\n\
                                <option value="">-- select --</option>\n\
                                <option value="1">Afternoon</option>\n\
                                <option value="0">Evening</option>\n\
                                <option value="2">Morning</option>\n\
                            </select>\n\
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
            text: "Are you sure? Delete this Slot!",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: true
        },
        function(){
            if(db_id){
                $.ajax({
                    url : '{{ route('admin_pick-up-slot-delete') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, id : db_id},
                    success : function(result){
                        $('#slider' + id).remove();
                        var total = $('input[name="total"]').val();
                        var total = parseInt(total) - 1;
                        $('input[name="total"]').val(total);
                        window.notyf.open({
                            type : 'success',
                            message : 'Slot Data Deleted Successfully!',
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
                    message : 'Slot Data Deleted Successfully!',
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
