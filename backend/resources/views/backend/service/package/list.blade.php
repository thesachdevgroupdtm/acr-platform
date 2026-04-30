@extends('backend.layout.main')
@section('css')
    <link rel="stylesheet" type="text/css" href="{{asset('public/plugins/sweetalert/sweetalert.css')}}">
    <link class="js-stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}" rel="stylesheet">
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
    <a href="{{route('admin_scheduled-package-create')}}" class="btn btn-success">
        <i class="align-middle" data-feather="plus"></i>{{__('Add')}}
    </a>
    
    <!-- Export All Prices Button -->
    <a href="{{ route('admin_export-all-prices') }}" class="btn btn-info" onclick="return confirm('Download all service prices?')">
        <i class="fas fa-download"></i> Export All Prices
    </a>
    
    <a href="javascript:void(0)" class="btn btn-primary" id="csv_link">
        <i class="fas fa-upload"></i> Import CSV
    </a>
    
    <a href="{{asset('public/samples/scheduled_package_pricing.csv')}}" class="btn btn-warning" download>
        <i class="fas fa-file-csv"></i> Download Sample CSV
    </a>
</div>
                        </div>
                        <div class = "row">
                            <div class="form-group col-md-2 select-parsley">
                                <label for="serviceCategory">Category</label>
                                <select id="serviceCategory" class="form-control select2" name="serviceCategory">
                                    <option value="all" selected>--Select--</option>
                                    @if($categories->count())
                                        @foreach($categories as $value)
                                            <option value="{{$value->id}}">{{$value->title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <?php /**<div class="form-group col-md-2 select-parsley">
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
                            <div class="form-group col-md-2 select-parsley">
                                <label for="carModel">Car Model</label>
                                <select id="carModel" class="form-control select2" name="carModel">
                                    <option value="all" selected>--Select--</option>
                                    @if($models->count())
                                        @foreach($models as $value)
                                            <option value="{{$value->id}}">{{$value->title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group col-md-2 select-parsley">
                                <label for="fuelType">Fuel Type</label>
                                <select id="fuelType" class="form-control select2" name="fuelType">
                                    <option value="all" selected>--Select--</option>
                                    @if($fuel_type->count())
                                        @foreach($fuel_type as $value)
                                            <option value="{{$value->id}}">{{$value->title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>**/ ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="table" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{__('Id')}}</th>
                                    <th>{{__('Image')}}</th>
                                    <th>{{__('Title')}}</th>
                                    <th>{{__('Category')}}</th>
                                    <?php /*<th>{{__('Car Detail')}}</th>*/ ?>
                                    <th>{{__('Note')}}</th>
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
<div class="modal fade csv-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myLargeModalLabel">Upload Schedule Package CSV</h5>
            </div>

            <form method="POST" action="{{ route('admin_import-schedule-package') }}" enctype="multipart/form-data" data-parsley-validate="">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="" id="" value="">
                
                <div class="modal-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="col-md-6">
                                <label for="description">Select File</label>
                            </div>
                            <div class="col-md-12">
                                <input type="file" id="myfile" name="myfile" required="" accept=".csv"><br><br>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection
@section('javascript')
<script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
<script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
<script>
$(document).ready(function() {
    $(document).on('click', '#csv_link', function(){
        $('.csv-modal').modal('show');
    });
    $('#serviceCategory').select2();
    var table = $("#table").DataTable({
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
            {data: 'category', name: 'category'},
            {data: 'note', name: 'note'},
            {data: 'time_takes', name: 'time_takes'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        "ajax" : {
            url : "{{ route('admin_scheduled-package-datatable') }}",
            type : "POST",
            data : function(d) {
                d._token = "{{ csrf_token() }}"
                d.serviceCategory = $('#serviceCategory').val()
            }
        }
    });

    $(document).on('change', '#serviceCategory', function(){
        table.ajax.reload();
    });

    $(document).on('click', '.delete', function() {
        var href = $(this).data('href');
        swal({
            title: "",
            text: "{{__('Are you sure? Delete this scheduled package!')}}",
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