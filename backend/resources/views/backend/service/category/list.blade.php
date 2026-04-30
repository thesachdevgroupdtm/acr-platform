@extends('backend.layout.main')
@section('css')
    <link rel="stylesheet" type="text/css" href="{{asset('public/plugins/sweetalert/sweetalert.css')}}">
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
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
                            <div class="col-md-12 text-end">
                                <div class="col-md-12 text-end"><a href="{{route('admin_service-category-create')}}" class="btn btn-success ajax-form" id=""><i class="align-middle" data-feather="plus"></i>{{__('Add')}}</a></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="categories" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{__('Reorder')}}</th>
                                    <th>{{__('Id')}}</th>
                                    <th>{{__('Icon')}}</th>
                                    <th>{{__('Title')}}</th> 
                                    <th>{{__('Description')}}</th> 
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
<div class="modal fade" id="form_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="form-detail">

        </div>
    </div>
</div>
@endsection
@section('javascript')
    <script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
    <script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
    <script>
        $(document).ready(function() {
            var categories = $("#categories").DataTable({
                "sScrollX": '100%',
                "order": [], //Initial no order.
                "aaSorting": [],
                processing: true,
                serverSide: true,
                "pageLength": 100,
                "lengthMenu": [[50, 100, 200, 400], [50, 100, 200, 400]],
                "columns": [
                    {data: 'order_by', name: 'order_by'},
                    {data: 'id', name: 'id'},
                    {data: 'image', name: 'image'},
                    {data: 'title', name: 'title'},
                    {data: 'description', name: 'description'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                "ajax" : {
                    url : "{{ route('admin_service-category-datatable') }}",
                    type : "POST",
                    data : function(d) {
                        d._token = "{{ csrf_token() }}"
                    }
                }
            });

            @if($errors->has('title'))
                var error = "{{$errors->first('title')}}";
                var id = "{{old('id')}}";
                ajaxForm(id);
                window.notyf.open({
                    type : 'error',
                    message : error,
                    duration : '10000',
                    ripple : true,
                    dismissible : true,
                    position: {
                        x: 'right',
                        y: 'top'
                    }
                });
            @endif

            $(document).on('click', '.ajax-form', function(){
                var id = $(this).data('id');
                ajaxForm(id);
            });
            
            $(document).on('click', '.status', function(){
                var id = $(this).data('id');
                var status = $(this).data('status');
                var message = status ? 'Active' : 'Inactive';
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                swal({
                    title: "",
                    text: "Are you sure? "+message+" this service category!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, "+message+" it!",
                    cancelButtonText: "{{__('Cancel')}}",
                    closeOnConfirm: true
                },
                function(){
                    $.ajax({
                        url : '{{ route('admin_change-service-category-status') }}',
                        method : 'post',
                        data : {_token: CSRF_TOKEN, id : id, status : status},
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
                            categories.ajax.reload();
                        }
                    });
                });
            });

            $(document).on('click', '.delete', function() {
                var href = $(this).data('href');
                swal({
                    title: "",
                    text: "{{__('Are you sure? Delete this service category!')}}",
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

            $(document).on("change", ".order_by", function() {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                var order_by = $(this).val();
                var category_id = $(this).data('categoryid');
                $.ajax({
                    type : "POST",
                    url  : "{{ route('admin_service-category-order-by') }}",
                    data : { _token: CSRF_TOKEN, category_id: category_id, order_by: order_by },
                    success : function(response) {

                    }
                })
            });
        });

        // 
    </script>
@endsection