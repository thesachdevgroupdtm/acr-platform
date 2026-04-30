<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Bootstrap 4 Admin &amp; Dashboard Template">
    <meta name="author" content="Bootlab">

    <title>{{'Reset Password | '.$site_name}}</title>

    <link rel="shortcut icon" href="{{ asset('img/favicon.png') }}">

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
                                <h1 class="h2">Reset Password</h1>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="m-sm-4">
                                        <div class="text-center">
                                            <img src="{{ asset('img/logo.png') }}" alt="{{$site_name}}" class="img-fluid" width="132" height="132" />
                                            <!--<h3>School</h3>-->
                                        </div>
                                        <form method="POST" action="{{ route('admin_set-new-password') }}" data-parsley-validate="">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="user_id" value="{{\Crypt::encrypt($user_id)}}">
                                            @if ($message = Session::get('errors'))
                                                <div class="alert alert-danger alert-dismissible" role="alert">
                                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                        <span aria-hidden="true">×</span>
                                                    </button>
                                                    <div class="alert-message">
                                                        {{ $errors->first('message') }}
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($message = Session::get('success'))
                                                <div class="alert alert-success alert-dismissible" role="alert">
                                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                        <span aria-hidden="true">×</span>
                                                    </button>
                                                    <div class="alert-message">
                                                        {{ $message }}
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="form-group">
                                                <label for="password">New Password</label>
                                                <input id="password" type="password" class="form-control" name="password" tabindex="2" required data-parsley-minlength="6">
                                            </div>
                                            <div class="form-group">
                                                <label for="password-confirm">Confirm Password</label>
                                                <input id="password-confirm" type="password" class="form-control" name="confirm-password" tabindex="2" required  data-parsley-equalto="#password" data-parsley-error-message="Confirm password should match password field.">
                                            </div>
                                            <div class="text-center mt-3">
                                                <button type="submit" class="btn btn-lg btn-primary">Submit</button>
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