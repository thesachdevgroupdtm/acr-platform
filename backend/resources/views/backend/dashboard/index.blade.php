@extends('backend.layout.main')
@section('css')
<style>
    .select2-container{
        width: 200px !important;
    }
</style>
@endsection
@section('content')
<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-12">
                @include('backend.alerts')
            </div>
        </div>
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
               <h3>{{$site_title}}</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-sm-4 col-xxl d-flex">
                <div class="card flex-fill bg-primary">
                    <div class="card-body py-4">
                        <a href="{{route('admin_products')}}">
                            <div class="d-flex align-items-start text-center">
                                <div class="flex-grow-1">
                                    <h2 class="mb-2 text-white"><i class=" fa fa-upload text-center"></i>&nbsp;{{$total_product}}</h2>
                                    <h1 class="mb-2 text-white fs-3 text">Total Product</h1>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-4 col-xxl d-flex">
                <div class="card flex-fill bg-danger">
                    <div class="card-body py-4">
                        <a href="{{route('admin_service-category')}}">
                            <div class="d-flex align-items-start text-center">
                                <div class="flex-grow-1">
                                    <h2 class="mb-2 text-white"><i class=" fa fa-upload text-center"></i>&nbsp;{{$total_service_category}}</h2>
                                    <h1 class="mb-2 text-white fs-3 text">Total Category</h1>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-4 col-xxl d-flex">
                <div class="card flex-fill bg-info">
                    <div class="card-body py-4">
                        <a href="{{route('admin_booked-services')}}">
                            <div class="d-flex align-items-start text-center">
                                <div class="flex-grow-1">
                                    <h2 class="mb-2 text-white"><i class=" fa fa-upload text-center"></i>&nbsp;{{$total_booked_service}}</h2>
                                    <h1 class="mb-2 text-white fs-3 text">Total Booked Service</h1>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-4 col-xxl d-flex">
                <div class="card flex-fill bg-success">
                    <div class="card-body py-4">
                        <a href="{{route('admin_order')}}">
                            <div class="d-flex align-items-start text-center">
                                <div class="flex-grow-1">
                                    <h2 class="mb-2 text-white"><i class=" fa fa-upload text-center"></i>&nbsp;{{$total_order}}</h2>
                                    <h1 class="mb-2 text-white fs-3 text">Total Order</h1>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-4 col-xxl d-flex">
                <div class="card flex-fill bg-warning">
                    <div class="card-body py-4">
                        <a href="{{route('admin_user')}}">
                            <div class="d-flex align-items-start text-center">
                                <div class="flex-grow-1">
                                    <h2 class="mb-2 text-white"><i class=" fa fa-upload text-center"></i>&nbsp;{{$total_user}}</h2>
                                    <h1 class="mb-2 text-white fs-3 text">Total User</h1>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
@section('javascript')
    <script>
        $(document).ready(function(){
        });
    </script>
@endsection