<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Bootstrap 4 Admin &amp; Dashboard Template">
    <meta name="author" content="Bootlab">

    <title>{{ __('Sign in').' | '.$site_name}}</title>

    <link rel="shortcut icon" href="{{ asset('public/favicon.ico') }}">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="{{ asset('css/light.css') }}" rel="stylesheet">
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-behavior="sticky">
    <div class="main d-flex justify-content-center w-100">
        <main class="content d-flex p-0">
            <div class="container d-flex flex-column">
                <div class="row h-100">
                    <div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
                        <div class="d-table-cell align-middle">

                            <div class="text-center mt-4">
                                <p class="lead">
                                    {{ __('Sign in to your account to continue')}}
                                </p>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="m-sm-4">
                                        <div class="text-center">
                                            <img src="{{ asset('front/images/logo.png') }}" alt="{{$site_name}}" class="img-fluid" width="132" height="132" />
                                            <!--<h3>DAMANFX</h3>-->
                                        </div>
                                        <form method="POST" action="{{ route('admin_checkedlogin') }}" data-parsley-validate="">
                                            {{ csrf_field() }}
                                            @if ($message = Session::get('errors'))
                                                <div class="alert alert-danger alert-dismissible" role="alert">
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                    <div class="alert-message">
                                                        {{ $errors->first('message') }}
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($message = Session::get('success'))
                                                <div class="alert alert-info alert-dismissible" role="alert">
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                    <div class="alert-message">
                                                        {{ $message }}
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="form-group">
                                                <label>{{ __('Email')}}</label>
                                                <input class="form-control form-control-lg" type="email" name="email" placeholder="{{__('Enter your email')}}" required="" data-parsley-error-message="{{ __("This value should be a valid email.")}}"/>
                                            </div>
                                            <div class="form-group">
                                                <label>{{ __('Password')}}</label>
                                                <input class="form-control form-control-lg" type="password" name="password" placeholder="{{__('Enter your password')}}" required="" data-parsley-required-message="{{ __("This value is required.")}}"/>
                                            </div>
                                            <div class="text-center mt-3">
                                                <button type="submit" class="btn btn-lg btn-primary">{{__('Sign in')}}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
</body>

</html>