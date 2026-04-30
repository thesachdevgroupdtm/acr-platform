@extends('front.layout.main')

@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
@endsection

@section('content')
<section class="page-header">
    <div class="page-header__bg"></div>
    <div class="container">
        <h1 class="page-header__title bw-split-in-right">Forgot</h1>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{ url('/') }}"><i class="flaticon-home"></i>Home</a></li>
            <li><span>Forgot</span></li>
        </ul>
    </div>
</section>

<section class="login-page">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 wow fadeInLeft" data-wow-delay="100ms">
                <!-- <div class="login-page__image">
                    <div class="login-page__image__one">
                        <img src="{{ asset('front/images/resources/login-1-1.jpg') }}" alt="Login">
                    </div>
                </div> -->
            </div>
            <div class="col-lg-6 wow fadeInUp" data-wow-delay="300ms">
                <div class="login-page__wrap">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('front/images/logo-light.png') }}" alt="Logo" width="131">
                    </a>
                    <h3 class="login-page__wrap__title">Nice to see you again</h3>
                    <form method="post" action="{{ route('front_forgot-password') }}" id="login-form" enctype="multipart/form-data" data-parsley-validate>
                        {{ csrf_field() }}
                        <div class="login-page__form-input-box">
                            
                            <input type="email" name="email" id="email" placeholder="Email ID" required class="form-control">
                        </div>
                        <div class="login-page__form-btn-box text-center mt-3">
                            <button type="submit" class="karoons-btn login-page__form-btn-box__login">
                                <span>Submit</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection

@section('javascript')
<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
@endsection
