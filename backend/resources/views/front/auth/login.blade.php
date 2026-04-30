@extends('front.layout.main')
@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
@endsection
@section('content')

    <div class="page-wrapper">
        <section class="page-header">
            <div class="page-header__bg"></div>
            <div class="container">
                <h1 class="page-header__title bw-split-in-right">Login</h1>
                <ul class="karoons-breadcrumb list-unstyled">
                    <li><a href="{{url('/')}}"><i class="flaticon-home"></i>Home</a></li>
                    <li><span>Login</span></li>
                </ul>
            </div>
        </section>

        <section class="login-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 wow fadeInLeft" data-wow-delay="100ms">
                        <div class="login-page__image">
                            <div class="login-page__image__one">
                                <img src="{{ asset('front/images/resources/login-1-1.jpg') }}" alt="ACR">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="300ms">
                        <div class="login-page__wrap">
                            <a href="">
                                <img src="{{ asset('front/images/logo-light.png') }}" alt="ACR" width="131">
                            </a>
                            <h3 class="login-page__wrap__title">Nice to see you again</h3>
                            <form method="post" action="{{ route('front_login') }}" id="login-form" enctype="multipart/form-data" data-parsley-validate>
                                {{ csrf_field() }}
                                <div class="login-page__form-input-box">
                                   
                                    <input type="email" name="email" id="email" placeholder="Email ID" required class="form-control">
                                </div>
                                <div class="login-page__form-input-box">
                                    
                                    <input type="password" name="password" id="password" placeholder="Enter password" class="form-control" required>
                                    <span class="login-page__form-input-box__icon"><i class="fas fa-eye"></i></span>
                                </div>
                                <div class="login-page__form-check-wrapper">
                                    <!-- <div class="login-page__checked-box">
                                        <input type="checkbox" name="remember" id="save-data">
                                        <label for="save-data"><span></span>Remember Me?</label>
                                    </div> -->
                                    <div class="login-page__form-forgot-password">
                                        <a href="{{ route('front_forgot-password') }}">Forgot your Password?</a>
                                    </div>
                                </div>
                                <div class="g-recaptcha" data-sitekey="6LftL4koAAAAABhdVUSqCTdh1j8R9Z1JYAZ8_5lT" data-callback="onSubmit"></div>
                                <div class="login-page__form-btn-box text-center mt-3">
                                    <button type="submit" class="karoons-btn login-page__form-btn-box__login">
                                        LOGIN
                                    </button>
                                    <div class="login-page__form-btn-box__border"></div>
                                    <!-- <a href="#" class="karoons-btn login-page__form-btn-box__google">
                                        <span><img src="{{ asset('front/images/shapes/google.png') }}" alt="ACR"> Or sign in with Google</span>
                                    </a> -->
                                    <p class="login-page__form-btn-box__register-text">Don’t have an account? <a href="{{ route('front_register') }}">Sign up now</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@section('javascript')
<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endsection
