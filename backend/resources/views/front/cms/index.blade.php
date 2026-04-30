@extends('front.layout.main')
@section('content')
<!-- contact-us page start  -->
 <div class="contact-section-main">
    <div class="container page-content">
        <div class="row">
            <div class="col-12 mx-auto my-lg-5 my-md-5 my-sm-3 my-3">
                <div class="pt-lg-4 pt-md-4 pt-sm-3 pt-3">
                    <h3 class="text-center page-head">{{$site_title}}</h3>
                    <div class="card-body pt-lg-3 pt-md-0 pt-sm-0 pt-0">
                       <p>{!! isset($pageInfo->description) ? $pageInfo->description : '' !!}</p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>    
<!-- contact-us page end -->
@endsection