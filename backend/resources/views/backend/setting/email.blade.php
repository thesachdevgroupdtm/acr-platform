@extends('backend.layout.main')
@section('css')
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
            <div class="col-12 col-md-3 col-xl-3">
                <div class="card">
                    <div class="card-header">
                    </div>

                    <div class="list-group list-group-flush" role="tablist">
                        @if($email_templates)
                            @foreach($email_templates as $key => $value)
                                <a href="#{{$value->label}}" data-bs-toggle="list" class="list-group-item list-group-item-action {{ $key == '0' ? 'active' : ''}}"  role="tab">{{$value->value}}</a>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-9 col-xl-9">
                <div class="tab-content">
                    @if($email_templates)
                        @foreach($email_templates as $key => $value)
                            <div class="tab-pane fade {{ $key == 0 ? 'active show' : ''}}" id="{{$value->label}}" role="tabpanel">
                                <div class="card">
                                    <form role="form" action="{{route('admin_email-templates')}}"  name="{{$value->label}}" method="post" data-parsley-validate enctype="multipart/form-data">
                                        @csrf
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <h2 class="col-form-label">{{$value->value}}</h2>
                                                <textarea class="form-control ckeditor" name="{{$value->label}}" id="{{$value->label}}" required="" style="height: 1000px">{{$value->template}}</textarea>
                                                <input type="hidden" name="id" value="{{$value->label}}">
                                            </div>
                                        </div>

                                        <div class="card-footer text-end">
                                            <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
                                        </div>
                                    </form>
                                </div>
                            </div><!-- /.tab-pane -->
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>

<meta name="csrf-token" content="{{ csrf_token() }}" />

@endsection

@section('javascript')

    <script src="{{ asset('plugins/parsley/parsley.js') }}"></script>

    <script src="{{asset('public/plugins/ckeditor/ckeditor.js')}}"  type="text/javascript"></script>

    <script>

        $(document).ready(function(){

            var editor = CKEDITOR.replaceAll( '.ckeditor', {

                height:1000,

                removePlugins : 'resize',

                filebrowserBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=files") ?>',

                filebrowserImageBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=images") ?>',

                filebrowserFlashBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=flash") ?>',

                filebrowserUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=files") ?>',

                filebrowserImageUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=images") ?>',

                filebrowserFlashUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=flash") ?>',

            });



            CKEDITOR.on('instanceReady', function () {

//                $('.ckeditor').attr('required', '');



                $.each(CKEDITOR.instances, function (instance) {

//                    instance.editor.resize("100%", '1000');

                    CKEDITOR.instances[instance].on("change", function (e) {

                        e.editor.resize("100%", '1000');

                        for (instance in CKEDITOR.instances) {

                            CKEDITOR.instances[instance].updateElement();

                            //$('form').parsley().validate();



                        }

                    });

                });

            });

        });

    </script>

@endsection
