@extends('backend.layout.main')
@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
@endsection
@section('content')
    <!-- Main Content -->
<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-12">
                @include('backend.alerts')
            </div>
        </div>
        <h1 class="h3 mb-3">{{$site_title}}</h1>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form  method="POST" action="{{ route('admin_smtp_update') }}" id="disseason-form" enctype="multipart/form-data" data-parsley-validate="">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sender_name" class="form-label">{{ __("Sender's Name")}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-left" id="sender_name" placeholder="{{ __("Sender's Name")}}" name="MAIL_FROM_NAME" required="" value="{{ $env_files['MAIL_FROM_NAME'] }}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_address" class="form-label">{{ __('MAIL ADDRESS')}} <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control text-left" id="mail_address" placeholder="{{ __('MAIL ADDRESS')}}" name="MAIL_FROM_ADDRESS" required="" value="{{ $env_files['MAIL_FROM_ADDRESS'] }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_mailer" class="form-label">{{ __('MAIL MAILER (ex. SMTP,MAIL)')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-left" id="mail_mailer" placeholder="{{ __('MAIL MAILER (ex. SMTP,MAIL)')}}" name="MAIL_MAILER" required="" value="{{ $env_files['MAIL_MAILER'] }}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_username" class="form-label">{{ __('MAIL USERNAME (ex. info@test.com)')}} <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control text-left" id="mail_username" placeholder="{{ __('MAIL USERNAME (ex. info@test.com)')}}" name="MAIL_USERNAME" required="" value="{{ $env_files['MAIL_USERNAME'] }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_host" class="form-label">{{ __('MAIL HOST (ex. smtp.mailtrap.io)')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-left" id="mail_host" placeholder="{{ __('MAIL HOST (ex. smtp.mailtrap.io)')}}" name="MAIL_HOST" required="" value="{{ $env_files['MAIL_HOST'] }}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_password" class="form-label">{{ __('MAIL PASSWORD')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-left" id="mail_password" placeholder="{{ __('MAIL PASSWORD')}}" name="MAIL_PASSWORD" required="" value="{{ $env_files['MAIL_PASSWORD'] }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_port" class="form-label">{{ __('MAIL PORT (ex. 2525,467)')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-left" id="mail_port" placeholder="{{ __('MAIL PORT (ex. 2525,467)')}}" name="MAIL_PORT" required="" value="{{ $env_files['MAIL_PORT'] }}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_enc" class="form-label">{{ __('MAIL ENCRYPTION (ex. TLS/SSL)')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-left" id="mail_enc" placeholder="{{ __('MAIL ENCRYPTION (ex. TLS/SSL)')}}" name="MAIL_ENCRYPTION" required="" value="{{ $env_files['MAIL_ENCRYPTION'] }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3"></label>
                                <div class="col-sm-12 col-md-7">
                                    <button type="submit" class="btn btn-primary">{{ __('Submit')}}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
@section('javascript')
    <script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
@endsection
