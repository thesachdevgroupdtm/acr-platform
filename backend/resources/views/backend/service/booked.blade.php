@extends('backend.layout.main')
@section('css')
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
                    <div class="card-header">
                        <div class = "row">
                            <div class="form-group col-md-3 select-parsley">
                                <label for="package">Scheduled Packages</label>
                                <select id="package" class="form-control select2" name="package">
                                    <option value="all" selected>--Select--</option>
                                    @if($packages->count())
                                        @foreach($packages as $value)
                                            <option value="{{$value->id}}">{{$value->title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group col-md-2 select-parsley">
                                <label for="brand">Brand</label>
                                <select id="brand" class="form-control select2" name="brand">
                                    <option value="all" selected>--Select--</option>
                                    @if($brands->count())
                                        @foreach($brands as $value)
                                            <option value="{{$value->id}}">{{$value->title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <?php /**<div class="form-group col-md-2 select-parsley">
                                <label for="carModel">Car Model</label>
                                <select id="carModel" class="form-control select2" name="carModel">
                                    <option value="all"selected>--Select--</option>
                                    @if($models->count())
                                        @foreach($models as $value)
                                            <option value="{{$value->id}}">{{$value->title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>**/ ?>

                            <div class="form-group col-md-2 select-parsley">
                                <label for="model_id">Car Model</label>
                                <select class="select2 form-control" name="model_id" id="model_id">
                                    <option value="">--select--</option>
                                </select>
                            </div>

                            <?php /* <div class="form-group col-md-2 select-parsley">
                                <label for="fuelType">Fuel Type</label>
                                <select id="fuelType" class="form-control select2" name="fuelType">
                                    <option value="all" selected>--Select--</option>
                                    @if($fuel_type->count())
                                        @foreach($fuel_type as $value)
                                            <option value="{{$value->id}}">{{$value->title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div> */ ?>

                            <div class="form-group col-md-2 select-parsley">
                                <label for="fuel_type_id">Fuel Type</label>
                                <select class="select2 form-control" name="fuel_type_id" id="fuel_type_id">
                                    <option value="">--select--</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="bservices" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{__('Id')}}</th>
                                    <th>{{__('Order No')}}</th>
                                    <th>{{__('User')}}</th>
                                    <th>{{__('Phone No')}}</th>
                                    <th>{{__('Service')}}</th>
                                    <th>{{__('Booked Date')}}</th>
                                    <th>{{__('Time')}}</th>
                                    <th>{{__('Time Takes')}}</th>
                                    <th>{{__('Action')}}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<div class="modal fade" id="slot_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{route('admin_change-service-slot')}}" id="slot-form" enctype="multipart/form-data" data-parsley-validate="">
        @csrf
        <input type="hidden" name="booking_id" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Change Slot Information</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body m-3">
                    <div class="Choose-service-date-main" id="service_slot_section">
                        <h4>Choose service date</h4>
                        <div class="date-sec-main">
                            @php($weekdays = weekOfDays('6'))
                            @if($weekdays)
                                @foreach($weekdays as $week)
                                    <a class="date-main slot-date" data-date="{{date('Y-m-d', strtotime($week))}}" href="javascript:void(0);">
                                    <p>{{$week}}<br/>{{date('l', strtotime($week))}}</p>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                        <div class="pick-slot-main">
                            <h4>Pick Time Slot <span id="total_slots">({{$aslots->count()+$eslots->count()}} slot available)</span> </h4>
                            <input type="hidden" name="slot_date" value="">
                            <input type="hidden" name="slot_time" value="">
                        </div>
                        <div id="slot_info">
                            @if($mslots->count())
                                <div class="afternoon-slot-sec-main">
                                    <h4><span>slots</span>Morning Slot</h4>
                                    <div class="row m-0">
                                        @foreach($mslots as $slot)
                                            <div class="col-12 col-sm-3">
                                                <a class="btn afternoon-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($aslots->count())
                                <div class="afternoon-slot-sec-main">
                                    <h4><span>slots</span>Afternoon Slot</h4>
                                    <div class="row m-0">
                                        @foreach($aslots as $slot)
                                            <div class="col-12 col-sm-3">
                                                <a class="btn afternoon-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($eslots->count())
                                <div class="evening-slot-sec-main">
                                    <h4><span>slots</span>Evening Slot</h4>
                                    <div class="row">
                                        @foreach($eslots as $slot)
                                            <div class="col-12 col-sm-3">
                                                <a class="btn evening-slot-btn slot-btn" data-id="{{$slot->time}}">{{$slot->time}}</a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection
@section('javascript')
<script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
<script>
$(document).ready(function() {
    $('.select2').select2();
    var bservices = $("#bservices").DataTable({
        "sScrollX": '100%',
        "order": [], //Initial no order.
        "aaSorting": [],
        processing: true,
        serverSide: true,
        "pageLength": 100,
        "lengthMenu": [[50, 100, 200, 400], [50, 100, 200, 400]],
        "columns": [
            {data: 'id', name: 'id'},
            {data: 'order_no', name: 'order_no'},
            {data: 'user', name: 'user'},
            {data: 'phone', name: 'phone'},
            {data: 'service', name: 'service'},
            {data: 'booked_date', name: 'booked_date'},
            {data: 'time', name: 'time'},
            {data: 'time_takes', name: 'time_takes'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        "ajax" : {
            url : "{{ route('admin_booked-service-datatable') }}",
            type : "POST",
            data : function(d) {
                d._token = "{{ csrf_token() }}",
                d.package = $('#package').val(),
                d.brand = $('#brand').val(),
                d.model_id = $('#carModel').val(),
                d.model = $('#model_id').val(),
                d.fuel_type = $('#fuelType').val(),
                d.fuel_type_id = $('#fuel_type_id').val(),
                d.od_id = "{{isset($od_id) ? $od_id : ''}}"
            }
        }
    });

    $(document).on('change', '#brand', function(){
        var brand = $(this).val();
        var model = $('#model_id').val();
        getModelFromBrand(brand);
        getFuelFromModel(brand,model);
    });

    $(document).on('change', '#model_id', function(){
        var model = $(this).val();
        var brand = $('#brand').val();
        getFuelFromModel(brand, model);
    });

    $(document).on('change', '#package,#brand,#carModel,#model_id,#fuel_type_id,#fuelType', function(){
        bservices.ajax.reload();
    });

    $(document).on('click', '.change_slot', function(){
        $('#slot_modal').modal('show');
        var id = $(this).data('id');
        $('input[name="booking_id"]').val(id);
    });

    $(document).on('click' , '.slot-btn', function(){
        var id = $(this).data('id');
        $('input[name="slot_time"]').val(id);
        $('.slot-btn').removeClass('evening-slot-active');
        $(this).addClass('evening-slot-active');
    });

    $(document).on('click', '.slot-date', function(){
        var date = $(this).data('date');
        $this = $(this);
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('front_get-available-slot') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, date:date},
            success : function(result){
                var result = $.parseJSON(result);
                $('#slot_info').html(result.html);
                $('input[name="slot_date"]').val(date);
                $('.slot-btn').removeClass('evening-slot-active');
                $('input[name="slot_time"]').val('');
                $('.slot-date').removeClass('select-date');
                $this.addClass('select-date');
                $('#total_slots').html("("+result.total_slots+" slot available)");
            }
        });
    });

    $("#slot-form").submit(function(e) {
        //e.preventDefault();
        var slot_time = $('input[name="slot_time"]').val();
        var slot_date = $('input[name="slot_date"]').val();
        if(slot_date == ''){
            window.notyf.open({
                type : 'error',
                message : 'Please select slot date!',
                duration : '10000',
                ripple : true,
                dismissible : true,
                position: {
                        x: 'right',
                        y: 'top'
                }
            });
            return false;
        } else if(slot_time == ''){
            window.notyf.open({
                type : 'error',
                message : 'Please select slot time!',
                duration : '10000',
                ripple : true,
                dismissible : true,
                position: {
                        x: 'right',
                        y: 'top'
                }
            });
            return false;
        } else {
           $("#slot-form").submit();
        }
   });
});

function getModelFromBrand(brand = ''){
    if(brand != '' || brand != null){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('admin_get-model-from-brand') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, brand : brand},
            success : function(result){
                var result = $.parseJSON(result);
                $('#model_id').html(result.html).trigger('change');
            }
        });
    }
}

function getFuelFromModel(brand = '', model = ''){
    if((brand != '') && (model != '' )){
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url : '{{ route('admin_get-fuel-from-model') }}',
            method : 'post',
            data : {_token: CSRF_TOKEN, brand : brand,model_id : model},
            success : function(result){
                var result = $.parseJSON(result);
                $('#fuel_type_id').html(result.html).trigger('change');
            }
        });
    }
    else {
        $('#fuel_type_id').html('<option value="">--select--</option>').trigger('change');
    }
}
</script>
@endsection

