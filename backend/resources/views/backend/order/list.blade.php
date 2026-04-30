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
                        <div class="form-row">
                            <div class="form-group col-md-2 ">
                                <label for="status">Status</label>
                                <select id="status" class="form-control select2" name="status">
                                    <option value="all" selected>--Select--</option>
                                    <option value="0">Pending</option>
                                    <option value="1">Complete</option>
                                    <option value="2">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-12 text-end">
                                <button class="btn btn-danger selected_data">Delete Selected</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="orders" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" id='checkAll' value="">
                                            <span class="form-check-label">
                                            {{__('id')}}
                                            </span>
                                        </label>
                                    </th>
                                    <th>{{__('Invoice No.')}}</th>
                                    <th>{{__('User')}}</th>
                                    <th>{{__('Email')}}</th>
                                    <th>{{__('Phone')}}</th>
                                    <th>{{__('Address')}}</th>
                                    <th>{{__('Zip')}}</th>
                                    <th>{{__('City')}}</th>
                                    <th>{{__('Total')}}</th>
                                    <th>{{__('Date')}}</th>
                                    <th>{{__('Status')}}</th>
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
    $("#checkAll").change(function() {
        if (this.checked) {
            $(".checkSingle").each(function() {
                this.checked=true;
            });
        } else {
            $(".checkSingle").each(function() {
                this.checked=false;
            });
        }
    });
    $('#status').select2();
    var order = $("#orders").DataTable({
        "sScrollX": '100%',
        "order": [], //Initial no order.
        "aaSorting": [],
        processing: true,
        serverSide: true,
        "pageLength": 100,
        "lengthMenu": [[50, 100, 200, 400], [50, 100, 200, 400]],
        "columns": [
            {data: 'id', name: 'id',orderable: false, searchable: false},
            {data: 'invoice_no', name: 'invoice_no'},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'phone', name: 'phone'},
            {data: 'address', name: 'address'},
            {data: 'zip', name: 'zip'},
            {data: 'city', name: 'city'},
            {data: 'total', name: 'total'},
            {data: 'odate', name: 'odate'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        "ajax" : {
            url : "{{ route('admin_order-datatable') }}",
            type : "POST",
            data : function(d) {
                d._token = "{{ csrf_token() }}"
                d.status = $("#status").val()
            }
        }
    });

    $(document).on('change', '#status', function(){
        order.ajax.reload();
    });

    $(document).on('click', '.delete', function() {
        var href = $(this).data('href');
        swal({
            title: "",
            text: "{{__('Are you sure? Delete this Order!')}}",
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

    $(document).on('click', '.complete', function(){
        var id = $(this).data('id');
        var complete = $(this).data('complete');
        var message = complete ? 'complete' : 'complete';
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        swal({
            title: "",
            text: "Are you sure? "+message+" this Order!",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "Yes, "+message+" it!",
            cancelButtonText: "{{__('Cancel')}}",
            closeOnConfirm: true
        },
        function(){
            $.ajax({
                url : '{{ route('admin_order-complete') }}',
                method : 'post',
                data : {_token: CSRF_TOKEN, id : id, complete : complete},
                success : function(result){
                    var res = $.parseJSON(result);
                    window.notyf.open({
                        type : 'success',
                        message : res.message,
                        duration : '10000',
                        ripple : true,
                        dismissible : true,
                        position: {
                                x: 'right',
                                y: 'top'
                        }
                    });
                    order.ajax.reload();
                }
            });
        });
    });

    $(document).on('click', '.selected_data', function(){
        var group = $(this).val();
        if(group != null || group != ''){
            var orderdelete = [];
            $('input.checkSingle:checkbox:checked').each(function () {
                orderdelete.push($(this).val());
            });
            if(jQuery.isEmptyObject(orderdelete)){
                swal("Please select data!");
            } else {
                swal({
                    title: "",
                    text: "Are you sure you want to Delete This Selected Data?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes",
                    closeOnConfirm: true
                }, function() {
                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url : "{{ route('admin_delete-order-data') }}",
                        method : 'post',
                        data : {_token : CSRF_TOKEN, group : group, orderdelete : orderdelete},
                        success : function(result){
                            var result = $.parseJSON(result);
                            if(result.result == 'success'){
                                swal(" ", "Order Deleted Successfully");
                                order.ajax.reload();
                            }else{
                                swal(" ", "Something went wrong, please try again later!");
                            }
                        }
                    });
                });
            }
        }
    });
});

</script>
@endsection

