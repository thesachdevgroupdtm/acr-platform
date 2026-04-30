@extends('backend.layout.main')
@section('css')
    <link rel="stylesheet" type="text/css" href="{{asset('public/plugins/sweetalert/sweetalert.css')}}">
    <link class="js-stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}" rel="stylesheet">
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
                        <h5 class="card-title mb-0">Basic Details</h5>
                    </div> 
                    <div class="card-body">
                        <span><b>Invoice No: </b>#{{$detail->invoice_no}}</span> <a class="badge bg-success" href="{{url('backend/invoice/'.$detail->invoice_no)}}" target="_blank">View Invoice</a><br/>
                        <span><b>Name: </b>{{$detail->name}}</span><br/>
                        <span><b>Email: </b>{{$detail->email}}</span><br/>
                        <span><b>Phone: </b>{{$detail->phone}}</span><br/>
                        <span><b>Address: </b>{{$detail->address.', '.$detail->zip.', '.$detail->city}}</span>
                    </div>
                </div>
                <div class="card">
                    <!-- <div class="card-header">
                        <div class="form-row">
                            <div class="col-md-12 text-end">
                                <div class="col-md-12 text-end"><a href="{{route('admin_faq-create')}}" class="btn btn-success"><i class="align-middle" data-feather="plus"></i>{{__('Add')}}</a></div>
                            </div>
                        </div>
                    </div> -->
                    <div class="card-body">
                        <input type="hidden" name="order_id" value="{{$detail->id}}">
                        <table id="order-detail" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{__('Id')}}</th>
                                    <th>{{__('Item')}}</th>
                                    <th>{{__('Price')}}</th>
                                    <th>{{__('Gst(%)')}}</th>
                                    <th>{{__('Qty')}}</th>
                                    <th>{{__('Total')}}</th>
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
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection
@section('javascript')
<script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
<script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
<script>
$(document).ready(function() {
    var detail = $("#order-detail").DataTable({
        "sScrollX": '100%',
        "order": [], //Initial no order.
        "aaSorting": [],
        processing: true,
        serverSide: true,
        "pageLength": 100,
        "lengthMenu": [[50, 100, 200, 400], [50, 100, 200, 400]],
        "columns": [
            {data: 'id', name: 'id'},
            {data: 'item', name: 'item'},
            {data: 'price', name: 'price'},
            {data: 'gst', name: 'gst'},
            {data: 'qty', name: 'qty'},
            {data: 'subtotal', name: 'subtotal'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        "ajax" : {
            url : "{{ route('admin_order-detail-datatable') }}",
            type : "POST",
            data : function(d) {
                d._token = "{{ csrf_token() }}",
                d.order_id = $('input[name="order_id"]').val()
            }
        }
    });

    $(document).on('click', '.delete', function() {
        var href = $(this).data('href');
        swal({
            title: "",
            text: "{{__('Are you sure? Delete this Order Detail!')}}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{__('Yes, delete it!')}}",
            cancelButtonText: "{{__('Cancel')}}",
            closeOnConfirm: true
        },
        function(){
            location.href = href;
        });
    });
});
</script>

@endsection