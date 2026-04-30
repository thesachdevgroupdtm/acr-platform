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
                                <div class="col-md-12 text-end"><a href="javascript:void(0)" class="btn btn-success ajax-form" id=""><i class="align-middle" data-feather="plus"></i>{{__('Add')}}</a>
                                <a href="javascript:void(0)" class="btn btn-icon icon-left btn-primary" id="csv_link"> Import CSV </a>
                                <a class="btn btn-warning" href="{{ route('admin_car-brand-csv-export') }}">{{__('Download Sample CSV')}}</a></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="carmaker" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{__('Id')}}</th>
                                    <th>{{__('Image')}}</th>
                                    <th>{{__('Title')}}</th> 
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
<div class="modal fade csv-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myLargeModalLabel">Upload Car Brand CSV</h5>
            </div>

            <form method="POST" action="{{ route('admin_car-brand-import') }}" enctype="multipart/form-data" data-parsley-validate="">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="" id="" value="">
                
                <div class="modal-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="description">Select File</label>
                            </div>
                            <div class="col-md-12">
                                <input type="file" id="file" name="file" required="" accept=".csv"><br><br>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="save" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}" />
<div class="modal fade" id="maker_form_modal" tabindex="-1" role="dialog" aria-hidden="true">
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
            $(document).on('click', '#csv_link', function(){
                $('.csv-modal').modal('show');
            });

            var carmakers = $("#carmaker").DataTable({
                "sScrollX": '100%',
                "order": [], //Initial no order.
                "aaSorting": [],
                processing: true,
                serverSide: true,
                "pageLength": 100,
                "lengthMenu": [[50, 100, 200, 400], [50, 100, 200, 400]],
                "columns": [
                    {data: 'id', name: 'id'},
                    {data: 'image', name: 'image'},
                    {data: 'title', name: 'title'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                "ajax" : {
                    url : "{{ route('admin_car-brand-datatable') }}",
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
                    text: "Are you sure? "+message+" this car brnd!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, "+message+" it!",
                    cancelButtonText: "{{__('Cancel')}}",
                    closeOnConfirm: true
                },
                function(){
                    $.ajax({
                        url : '{{ route('admin_change-car-brand-status') }}',
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
                            carmakers.ajax.reload();
                        }
                    });
                });
            });

            $(document).on('click', '.delete', function() {
                var href = $(this).data('href');
                swal({
                    title: "",
                    text: "{{__('Are you sure? Delete this car brand!')}}",
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

        function ajaxForm(id = ''){
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url : '{{ route('admin_ajax-edit-brand-html') }}',
                method : 'post',
                data : {_token: CSRF_TOKEN, id:id},
                success : function(result){
                    var result = $.parseJSON(result);
                    $('#form-detail').html(result.html);
                    $('#maker_form_modal').modal('show');
                    $('form').parsley();
                }
            });
        }

        //use when import from another tab(browser)....not by pop-up
        // $(document).on('click', '#import', function(){
        //         location.href = "<?php echo route('admin_car-brand-import-add') ?>"
        //     });
    </script>
@endsection
