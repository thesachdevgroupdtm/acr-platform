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
        <div class="row col-12">
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Basic Details</h5>
                    </div>
                    <div class="card-body text-center">
                        @if(isset($detail->image) && $detail->image)
                            <img src="{{asset('public/uploads/user/'.$detail->image)}}" alt="Stacie Hall" class="img-fluid rounded-circle mb-2" width="128" height="128">
                        @endif
                        <h5 class="card-title mb-0">{{$detail->firstname. ' '.$detail->lastname}}</h5>
                        <div class="text-muted mb-2">{{$detail->email}}</div>
                        <div class="text-muted mb-2">{{$detail->phone}}</div>
                    </div>
                </div>
            </div>
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Address Details</h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="user_id" value="{{isset($detail->id) ? $detail->id : NULL}}">
                        <table id="user-address" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{__('Id')}}</th>
                                    <th>{{__('User')}}</th>
                                    <th>{{__('Address')}}</th>
                                    <th>{{__('Zip')}}</th>
                                    <th>{{__('City')}}</th>
                                    <th>{{__('Action')}}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <table id="orders" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{__('Id')}}</th>
                                    <th>{{__('Invoice No.')}}</th>
                                    <th>{{__('Name')}}</th>
                                    <th>{{__('Email')}}</th>
                                    <th>{{__('Phone')}}</th>
                                    <th>{{__('Address')}}</th>
                                    <th>{{__('Zip')}}</th>
                                    <th>{{__('City')}}</th>
                                    <th>{{__('Total')}}</th>
                                    <th>{{__('Date')}}</th>
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
    var address = $("#user-address").DataTable({
        "order": [], //Initial no order.
        "aaSorting": [],
        processing: true,
        serverSide: true,
        "pageLength": 100,
        "lengthMenu": [[50, 100, 200, 400], [50, 100, 200, 400]],
        "columns": [
            {data: 'id', name: 'id'},
            {data: 'user', name: 'user'},
            {data: 'address', name: 'address'},
            {data: 'zip', name: 'zip'},
            {data: 'city', name: 'city'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        "ajax" : {
            url : "{{ route('admin_user-address-datatable') }}",
            type : "POST",
            data : function(d) {
                d._token = "{{ csrf_token() }}",
                d.user_id = $('input[name="user_id"]').val()
            }
        }
    });

    $(document).on('click', '.delete', function() {
        var href = $(this).data('href');
        swal({
            title: "",
            text: "{{__('Are you sure? Delete this user Address!')}}",
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

    var order = $("#orders").DataTable({
        "sScrollX": '100%',
        "order": [], //Initial no order.
        "aaSorting": [],
        processing: true,
        serverSide: true,
        "pageLength": 100,
        "lengthMenu": [[50, 100, 200, 400], [50, 100, 200, 400]],
        "columns": [
            {data: 'id', name: 'id'},
            {data: 'invoice_no', name: 'invoice_no'},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'phone', name: 'phone'},
            {data: 'address', name: 'address'},
            {data: 'zip', name: 'zip'},
            {data: 'city', name: 'city'},
            {data: 'total', name: 'total'},
            {data: 'odate', name: 'odate'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        "ajax" : {
            url : "{{ route('admin_order-datatable') }}",
            type : "POST",
            data : function(d) {
                d._token = "{{ csrf_token() }}",
                d.user_id = "{{ $detail->id }}"
            }
        }
    });
});
</script>

@endsection