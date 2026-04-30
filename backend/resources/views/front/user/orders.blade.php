@extends('front.layout.main')
@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('public/plugins/sweetalert/sweetalert.css')}}">
    <link class="js-stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{asset('plugins/datatables/css/dataTables.bootstrap5.min.css')}}" rel="stylesheet">
@endsection
@section('content')
<div class="shop-center-tophead">
    <img src="{{ asset('front/img/service-inner-bg.png') }}" class="img-fluid" alt="">
    <div class="shop-center-text">
        <h2>{{ strtoupper($site_title) }}</h2>
        <ul class="shop-center-breadcum">
            <li><a href="{{url('/')}}">Home</a></li>
            <li><i class="fa-solid fa-angles-right"></i></li>
            <li>{{ $site_title }}</li>
        </ul>
    </div>
</div>

<div class="faq-section-main">
    <div class="container">
        <div class="row justify-content-center">
            <div class=" col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row table-responsive">
                            <table class="table table-success table-striped" id="ordertable">
                                <thead>
                                  <tr>
                                    <th scope="col">#Inovice No</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Item</th>
                                    <th scope="col">Address</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Action</th>
                                  </tr>
                                </thead>
                                <tbody>
                                    @if($orders->count())
                                        @foreach($orders as $order)
                                            @php($detail = isset($order->detail) && $order->detail->count() ? $order->detail : array())
                                            @if($detail)
                                                <tr>
                                                    <td>{{'#'.$order->invoice_no}}</td>
                                                    <td>{{$order->order_date ? date('d/m/Y', strtotime($order->order_date)) : ''}}</td>
                                                    <td>
                                                        @php($is_service_in_order = 0)
                                                        @foreach($detail as $record)
                                                            @if($record->service_id)
                                                                @php($is_service_in_order = $order->id)
                                                                @php($service_category = isset($record->packageDetail->packageDetail->categoryDetail->title) ? $record->packageDetail->packageDetail->categoryDetail->title : NULL)
                                                                @php($service = isset($record->packageDetail->packageDetail->title) ? $record->packageDetail->packageDetail->title : NULL)
                                                                @php($brand = isset($record->packageDetail->brandDetail->title) ? $record->packageDetail->brandDetail->title : NULL)
                                                                @php($model = isset($record->packageDetail->modelDetail->title) ? $record->packageDetail->modelDetail->title : NULL)
                                                                @php($fuel = isset($record->packageDetail->fuelTypeDetail->title) ? $record->packageDetail->fuelTypeDetail->title : NULL)
                                                                {{$service_category}}<br/>
                                                                {{$service}}<br/>
                                                                <small class="font-small">
                                                                    {{$brand.' - '.$model.' - '.$fuel}}<br/>
                                                                    @if(isset($order->vehicle_number) && $order->vehicle_number)
                                                                        {{'Vehicle Number : '.$order->vehicle_number}}<br/>
                                                                    @endif
                                                                    {{'Price : '.$record->price.', Gst(%) : '.$order->service_gst_rate}}<br/>
                                                                </small>
                                                                <small class="font-small text-danger">
                                                                    <b>Pick Up Details : 
                                                                        {{isset($order->slotDetail->slot_date) && $order->slotDetail->slot_date ? date('d/m/Y', strtotime($order->slotDetail->slot_date)) : '' }}
                                                                        {{isset($order->slotDetail->id) ? " ".$order->slotDetail->pick_up_time1.'-'.$order->slotDetail->pick_up_time2 : ''}}
                                                                        {{isset($order->slotDetail->time_type) && $order->slotDetail->time_type == '1' ? " PM" : ' AM'}}
                                                                        {{isset($order->slotDetail->time_takes) && $order->slotDetail->time_takes ? ', time takes '.$order->slotDetail->time_takes. ' hrs' : ''}}
                                                                    </b>
                                                                </small>
                                                                <hr/>
                                                            @endif
                                                            @if($record->product_id)
                                                                {{isset($record->productDetail->name) ? $record->productDetail->name : NULL}}<br/>
                                                                <small class="font-small">{{'Qty : '.$record->qty.', Price : '.$record->price.', Gst(%) : '.$order->product_gst_rate}}</small><br/>
                                                                <hr/>
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        {{$order->name}},<br/>
                                                        {{$order->email}},<br/>
                                                        {{$order->phone}},<br/>
                                                        {{$order->address}},<br/>
                                                        {{$order->city}},<br/>
                                                        {{$order->zip}}<br/>
                                                    </td>
                                                    <td>₹{{formatNumber($order->total)}}</td>
                                                    <td>
                                                        @if($order->is_complete == '0')
                                                            <a href='javascript:void(0);' data-href='{{route('front_cancel-order', array(\Crypt::encrypt($order->id)))}}' class='badge bg-danger cancle-order-btn  cancel'>Cancel Order</a><br/>
                                                        @elseif($order->is_complete == '1')
                                                            <span class='badge bg-primary completed-pdfbtn'>Completed</span><br/>
                                                        @elseif($order->is_complete == '2')
                                                            <span class='badge bg-info cancelled-pdfbtn'>Cancelled</span><br>
                                                        @endif
                                                        @if($is_service_in_order != 0 && $order->is_complete == '0')
                                                            <a href='javascript:void(0)' class='badge bg-warning change-slot-pdfbtn change_slot' data-id='{{$is_service_in_order}}'>Change Slot</a><br/>
                                                        @endif
                                                        <a href="{{url('invoice/'.$order->invoice_no)}}" rel='tooltip' title='View Order Pdf' target="_blank" class='badge bg-success  view-invoice-pdfbtn'>View Invoice</a><br/>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}" />
<div class="modal fade" id="slot_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{route('front_change-service-slot')}}" id="slot-form" enctype="multipart/form-data" data-parsley-validate="">
        @csrf
        <input type="hidden" name="order_id" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Change Slot Information</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body m-3">
                    <div class="col-12">
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
                                <h4>Pick Time Slot <span id="total_slots">({{$aslots->count()+$eslots->count()+$mslots->count()}} slot available)</span> </h4>
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
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('javascript')
<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
<script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
<script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
<script src="{{asset('plugins/datatables/js/jquery.dataTables.min.js')}}" type="text/javascript"></script>
<script src="{{asset('plugins/datatables/js/dataTables.bootstrap5.min.js')}}" type="text/javascript"></script>

<script>
    $(document).ready(function(){
        $('#ordertable').DataTable({
            "ordering": false,
            "bInfo" : false,
            "searching" : false
        });

        $(document).on('click', '.cancel', function(){
            var href = $(this).data('href');
            swal({
                title: "",
                text: "Are you sure? Canecl this Order!",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "Yes, cancel it!",
                closeOnConfirm: true
            },
            function(){
                location.href = href;
            });
        });

        $(document).on('click', '.change_slot', function(){
            $('#slot_modal').modal('show');
            var id = $(this).data('id');
            $('input[name="order_id"]').val(id);
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
                toastr.error('Please select slot date!');
                return false;
            } else if(slot_time == ''){
                toastr.error('Please select slot time!');
                return false;
            } else {
               $("#slot-form").submit();
            }
       });
    });
</script>
@endsection
