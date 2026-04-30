@extends('front.layout.main')

@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
@endsection

@section('content')
<section class="page-header">
    <div class="page-header__bg"></div>
    <div class="container">
        <h2 class="page-header__title bw-split-in-right">Register</h2>
        <ul class="karoons-breadcrumb list-unstyled">
            <li><a href="{{ url('/') }}"><i class="flaticon-home"></i>Home</a></li>
            <li><span>Register</span></li>
        </ul>
    </div>
</section>

<section class="login-page">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 wow fadeInLeft" data-wow-delay="100ms">
                <div class="login-page__image">
                    <div class="login-page__image__one">
                        <img src="{{ asset('front/images/resources/login-1-1.jpg') }}" alt="Register">
                    </div>
                </div>
            </div>
            <div class="col-lg-6 wow fadeInUp" data-wow-delay="300ms">
                <div class="login-page__wrap">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('front/images/logo-light.png') }}" alt="Logo" width="131">
                    </a>
                    <h3 class="login-page__wrap__title">Create an Account</h3>
                    <form method="post" action="" id="register-form" enctype="multipart/form-data" data-parsley-validate>
                        {{ csrf_field() }}
                        <div class="login-page__form-input-box">
                            
                            <input type="text" name="firstname" id="firstname" value="{{ old('firstname') }}" placeholder="Name" required class="form-control">
                        </div>
                        <div class="login-page__form-input-box">
                            
                            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" maxlength="10" placeholder="Phone No" required class="form-control num_only">
                        </div>
                        <div class="login-page__form-input-box">
                            
                            <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Email ID" required class="form-control">
                        </div>
                        <div class="login-page__form-input-box">
                            
                            <input type="password" name="password" id="password" placeholder="Password" class="form-control" required data-parsley-minlength="8" data-parsley-pattern="(?=.*[a-z])(?=.*[0-9])(?=.*[A-Z]).*" data-parsley-pattern-message="Your password must be at least 8 characters long and include at least 1 lowercase, 1 uppercase letter, and 1 number.">
                            <small>Note: Your password must be at least 8 characters long and include at least 1 lowercase, 1 uppercase letter, and 1 number.</small>
                        </div>
                        <div class="login-page__form-input-box">
                            
                            <input type="password" name="cpassword" id="cpassword" placeholder="Confirm Password" class="form-control" required data-parsley-equalto="#password" data-parsley-required-message="Confirm password should match the password field.">
                        </div>
                        <!--<div class="g-recaptcha" data-sitekey="6LftL4koAAAAABhdVUSqCTdh1j8R9Z1JYAZ8_5lT" data-callback="onSubmit"></div>-->
                        <div class="login-page__form-btn-box text-center mt-3">
                            <button type="submit" class="karoons-btn login-page__form-btn-box__login">Sign up</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
<!--<script src="https://www.google.com/recaptcha/api.js" async defer></script>-->
@endsection
