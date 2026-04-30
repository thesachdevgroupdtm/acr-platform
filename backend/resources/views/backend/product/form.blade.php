@extends('backend.layout.main')
@section('css')
    <link class="js-stylesheet" href="{{ asset('plugins/parsley/parsley.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('public/plugins/sweetalert/sweetalert.css')}}">
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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="@if(isset($record->id)) {{ route('admin_product-update', array('id' => Crypt::encrypt($record->id))) }} @else{{route('admin_product-store')}} @endif" id="user-form" enctype="multipart/form-data" data-parsley-validate="">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <div class="box-body">
                                <div class="row">
                                    <div class="mb-3 col-md-4 select-parsley">
                                        <label for="shop_category_id" class="form-label">Shop Category<span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="shop_category_id" id="shop_category_id" required="">
                                            <option value="">--select--</option>
                                            @foreach($shop_category as $shop_cate)
                                                <option value="{{$shop_cate->id}}" @if(isset($record->shop_category_id) && $record->shop_category_id == $shop_cate->id){{'selected'}}@endif>{{$shop_cate->name}}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('shop_category_id')) <div class="text-danger">{{ $errors->first('shop_category_id') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-8">
                                        <label class="form-label" for="name">{{__('Alt Text')}}<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" placeholder="{{__('Alt Text')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->name) ? $record->name : old('name') }}">
                                        @if ($errors->has('name')) <div class="text-danger">{{ $errors->first('name') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="description">{{__('Description')}}</label>
                                        <textarea class="form-control" id="description" name="description" placeholder="{{__('Description')}}">{{ isset($record->description) ? $record->description : old('description') }}</textarea>
                                        @if ($errors->has('description')) <div class="text-danger">{{ $errors->first('description') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="specification">{{__('Specification')}}</label>
                                        <textarea class="form-control" id="specification" name="specification" placeholder="{{__('Specification')}}">{{ isset($record->specification) ? $record->specification : old('specification') }}</textarea>
                                        @if ($errors->has('specification')) <div class="text-danger">{{ $errors->first('specification') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="amazon_link">{{__('Amazon Link')}}</label>
                                        <input type="url" class="form-control" id="amazon_link" name="amazon_link" placeholder="{{__('Amazon Link')}}" data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->amazon_link) ? $record->amazon_link : old('amazon_link') }}">
                                        @if ($errors->has('amazon_link')) <div class="text-danger">{{ $errors->first('amazon_link') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label class="form-label" for="flipcart_link">{{__('Flipcart Link')}}</label>
                                        <input type="url" class="form-control" id="flipcart_link" name="flipcart_link" placeholder="{{__('Flipcart Link')}}" data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->flipcart_link) ? $record->flipcart_link : old('flipcart_link') }}">
                                        @if ($errors->has('flipcart_link')) <div class="text-danger">{{ $errors->first('flipcart_link') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-3">
                                        <label class="form-label" for="price">{{__('Price')}}<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control numeric" required="" id="price" name="price" placeholder="{{__('Price')}}" data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->price) ? $record->price : old('price') }}">
                                        @if ($errors->has('price')) <div class="text-danger">{{ $errors->first('price') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="sku">{{__('Sku')}}<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="sku" name="sku" placeholder="{{__('Sku')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->sku) ? $record->sku : old('sku') }}">
                                        @if ($errors->has('sku')) <div class="text-danger">{{ $errors->first('sku') }}</div>@endif
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="slug">{{__('Slug')}}<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="slug" placeholder="{{__('Slug')}}" value="{{ isset($record->slug) ? $record->slug : old('slug') }}" required="">
                                        <input type='hidden' name="slug"  value="{{ isset($record->slug) ? $record->slug : old('slug') }}">
                                        @if ($errors->has('slug')) <div class="text-danger">{{ $errors->first('slug') }}</div>@endif
                                    </div>
<!-- 
                                    <div class="mt-3  col-md-12">
                                        <h6>SEO Details</h6>
                                        <hr/>
                                    </div> -->
                                </div>
                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="meta_title">{{__('Meta Title')}}</label>
                                        <input type="text" class="form-control" id="meta_title" name="meta_title" placeholder="{{__('Meta Title')}}" value="{{ isset($record->meta_title) ? $record->meta_title : old('meta_title') }}">
                                        @if ($errors->has('meta_title')) <div class="text-danger">{{ $errors->first('meta_title') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="meta_keywords">{{__('Meta Keyword')}}</label>
                                        <textarea class="form-control" id="meta_keywords" name="meta_keywords" placeholder="{{__('Meta Keyword')}}">{{ isset($record->meta_keywords) ? $record->meta_keywords : old('meta_keywords') }}</textarea>
                                        @if ($errors->has('meta_keywords')) <div class="text-danger">{{ $errors->first('meta_keywords') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="meta_description">{{__('Meta Description')}}</label>
                                        <textarea class="form-control" id="meta_description" name="meta_description" placeholder="{{__('Meta Description')}}">{{ isset($record->meta_description) ? $record->meta_description : old('meta_description') }}</textarea>
                                        @if ($errors->has('meta_description')) <div class="text-danger">{{ $errors->first('meta_description') }}</div>@endif
                                    </div>

                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="canonical_tag">{{__('Canonical Tag')}}</label>
                                        <input type="text" class="form-control" id="canonical_tag" name="canonical_tag" placeholder="{{__('Canonical Tag')}}" value="{{ isset($record->canonical_tag) ? $record->canonical_tag : old('canonical_tag') }}">
                                        @if ($errors->has('canonical_tag')) <div class="text-danger">{{ $errors->first('canonical_tag') }}</div>@endif
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <br>
                                        <label class="form-check" for="featured_product"><input type="checkbox" id="featured_product" name="featured_product" value="" @if(!empty($record->featured)) {{ "checked" }} @endif> {{__('Featured Product')}}</label>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <br>
                                        <label class="form-check" for="onsale"><input type="checkbox" id="onsale" name="onsale" value="" @if(!empty($record->onsale)) {{ "checked" }} @endif> {{__('On Sale')}}</label>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label class="form-label" for="inventory">{{__('Instock Items')}}</label>
                                        <input type="text" class="form-control" id="inventory" name="inventory" placeholder="{{__('Canonical Tag')}}" value="{{ isset($record->inventory) ? $record->inventory : old('inventory') }}">
                                    </div>
                                </div>

                                <div class="form-group col-md-12">
                                    <div class="col-md-12 d-flex px-0">
                                        <label class="form-label w-100">Image<span class="text-danger">(For Best resolution please upload 400*500 size and in WebP file format.)</span></label>
                                        <a href="Javascript:void(0);" id="product-image-add" class="btn btn-success" style="width: 11vw;"><i class="fas fa-fw fa-plus align-middle"></i> Add more</a>
                                    </div>
                                    <div id="product-images" class="col-md-12">
                                        <div class="row image-head my-2">
                                            <div class="col-md-6"><strong>Image File<br><small>(Image URL : https://drive.google.com/uc?export=view&id=[FILE_CODE])</small></strong></div>
                                            <div class="col-md-2 text-center"><strong>Primary</strong></div>
                                            <div class="col-md-2"><strong>Image Title</strong></div>
                                            <div class="col-md-2"><strong>Action</strong></div>
                                        </div>
                                        @php($total = 0)
                                        @if(isset($record) && $record->images->count() > 0)
                                            @php($total = $record->images->count())
                                            @foreach($record->images as $pkey => $pval)
                                                <div class="row image-row image-{{$pkey}} mb-2">
                                                    <input type="hidden" name="pid{{$pkey}}" value="{{ $pval->id }}">
                                                    <div class="col-12">
                                                        <hr/>
                                                    </div>
                                                    <div class="col-md-4 pl-0">
                                                        <div class="profile-icon">
                                                            <img class='img-responsive img-fluid' id="uploadPreview{{$pkey}}" src="{{url('public/uploads/product/'.$pval->product_id.'/'.$pval->image)}}"  alt=''>
                                                        </div>
                                                        <div class="m-b-10">
                                                            <input type="file" id="uploadImage{{$pkey}}" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image{{$pkey}}" data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage({{$pkey}});" >
                                                        </div>
                                                    </div>
                                                    <?php /**<div class="col-md-6 mb-3">
                                                        <label class="form-label" for="image">{{__('Image')}}</label>
                                                        <input type="url" class="form-control"  value="{{ isset($pval->image) ? $pval->image : '' }}" name="image{{$pkey}}" placeholder="{{__('Image')}}">
                                                    </div>**/ ?>
                                                    <div class="col-md-2 pl-0 text-center">
                                                        <br/><input type="radio" class="" value="{{$pkey}}" name="is_primary" @if($pval->is_primary == '1') checked="checked" @endif/>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <label class="form-label" for="image_title">{{__('Image Title')}}</label>
                                                        <input type="text" class="form-control"  value="{{ isset($pval->image_title) ? $pval->image_title : '' }}" name="image_title{{$pkey}}" placeholder="{{__('Image Title')}}">
                                                    </div>
                                                    <div class="col-md-2 pl-0">
                                                        <br/><span class="btn btn-danger btn-sm delete" data-id="{{$pkey}}" data-db_id="{{$pval->id}}"><i class="fas fa-trash"></i></span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                        <input type="hidden" name="total" value="{{$total}}">
                                        <input type="hidden" name="last_id" value="{{$total}}">
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="{{route('admin_products')}}" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection
@section('javascript')
    <script src="{{ asset('plugins/parsley/parsley.js') }}"></script>
    <script src="{{asset('public/plugins/ckeditor/ckeditor.js')}}"  type="text/javascript"></script>
    <script src="{{asset('plugins/sweetalert/sweetalert.js')}}" type="text/javascript"></script>
    <script>
        $(document).ready(function(){
            $('.select2').select2();
             // $(document).ready(function(){
                //     CKEDITOR.replaceAll(function(textarea,config){
                //     config.height='200px';
                //     config.toolbarGroups=[
                //     {name:'document',groups:['mode','document','doctools']},
                //     {name:'clipboard',groups:['clipboard','undo']},
                //     {name:'editing',groups:['find','selection','spellchecker','editing']},
                //     {name:'forms',groups:['forms']},
                //     {name:'basicstyles',groups:['basicstyles','cleanup']},
                //     {name:'paragraph',groups:['list','indent','blocks','align','bidi','paragraph']},
                //     {name:'links',groups:['links']},
                //     {name:'insert',groups:['insert']},
                //     {name:'styles',groups:['styles']},
                //     {name:'colors',groups:['colors']},
                //     {name:'tools',groups:['tools']},
                //     {name:'others',groups:['others']},
                //     {name:'about',groups:['about']}
                //     ];
                //     config.removeButtons='Save,NewPage,Preview,Print,Templates,Cut,Copy,Paste,PasteText,PasteFromWord,Undo,Redo,Find,Replace,SelectAll,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Strike,Subscript,Superscript,CopyFormatting,RemoveFormat,NumberedList,BulletedList,Outdent,Indent,Blockquote,CreateDiv,JustifyLeft,JustifyCenter,JustifyRight,JustifyBlock,BidiLtr,BidiRtl,Language,Link,Unlink,Anchor,Image,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,Maximize,ShowBlocks,About';
                // });
            // });
            $(document).ready(function(){
                CKEDITOR.replace('description', {
                    height:200,
                    removePlugins : 'resize',
                    filebrowserBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=files") ?>',
                    filebrowserImageBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=images") ?>',
                    filebrowserFlashBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=flash") ?>',
                    filebrowserUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=files") ?>',
                    filebrowserImageUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=images") ?>',
                    filebrowserFlashUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=flash") ?>',
                });
                CKEDITOR.on('instanceReady', function () {
                    $('#description').attr();
                    $.each(CKEDITOR.instances, function (instance) {
                        CKEDITOR.instances[instance].on("change", function (e) {
                            for (instance in CKEDITOR.instances) {
                                CKEDITOR.instances[instance].updateElement();
                                //$('form').parsley().validate();
                            }
                        });
                    });
                });
            });

            $(document).ready(function(){
                CKEDITOR.replace('specification', {
                    height:200,
                    removePlugins : 'resize',
                    filebrowserBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=files") ?>',
                    filebrowserImageBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=images") ?>',
                    filebrowserFlashBrowseUrl : '<?php echo url("public/plugins/kcfinder/browse.php?opener=ckeditor&type=flash") ?>',
                    filebrowserUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=files") ?>',
                    filebrowserImageUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=images") ?>',
                    filebrowserFlashUploadUrl : '<?php echo url("public/plugins/kcfinder/upload.php?opener=ckeditor&type=flash") ?>',
                });
                CKEDITOR.on('instanceReady', function () {
                    $('#specification').attr();
                    $.each(CKEDITOR.instances, function (instance) {
                        CKEDITOR.instances[instance].on("change", function (e) {
                            for (instance in CKEDITOR.instances) {
                                CKEDITOR.instances[instance].updateElement();
                                //$('form').parsley().validate();
                            }
                        });
                    });
                });
            });

            $("#product-image-add").click(function() {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                var total = $('input[name="total"]').val();
                var last_id = $('input[name="last_id"]').val();
                console.log(last_id);
                $.ajax({
                    url : '{{ route('admin_product-image-ajax-html') }}',
                    method : 'post',
                    data : {_token: CSRF_TOKEN, id : last_id},
                    success : function(result){
                        var result = $.parseJSON(result);
                        var html = result.html;
                        $("#product-images").append(html);
                    }
                });
                var total = parseInt(total) + 1;
                $('input[name="total"]').val(total);
                var lastId = parseInt(last_id) + 1;
                $('input[name="last_id"]').val(lastId);
            });

            $(document).on('click', '.delete', function(){
                var db_id = $(this).data('db_id');
                var id = $(this).data('id');
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                swal({
                    title: "",
                    text: "Are you sure? Delete this Image!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, delete it!",
                    closeOnConfirm: true
                },
                function(){
                    if(db_id){
                        $.ajax({
                            url : '{{ route('admin_product-image-delete') }}',
                            method : 'post',
                            data : {_token: CSRF_TOKEN, id : db_id},
                            success : function(result){
                                console.log(id);
                                $('.image-'+ id).remove();
                                var total = $('input[name="total"]').val();
                                var total = parseInt(total) - 1;
                                $('input[name="total"]').val(total);
                                window.notyf.open({
                                    type : 'success',
                                    message : 'Image Deleted Successfully!',
                                    duration : '10000',
                                    ripple : true,
                                    dismissible : true,
                                    position: {
                                            x: 'right',
                                            y: 'top'
                                    }
                                });
                            }
                        });
                    } else {
                        $('.image-' + id).remove();
                        var total = $('input[name="total"]').val();
                        var total = parseInt(total) - 1;
                        $('input[name="total"]').val(total);
                        window.notyf.open({
                            type : 'success',
                            message : 'Image Deleted Successfully!',
                            duration : '10000',
                            ripple : true,
                            dismissible : true,
                            position: {
                                    x: 'right',
                                    y: 'top'
                            }
                        });
                    }
                });              
            });

            $(document).on("click", ".remove-product-image", function() {
                $(this).closest(".image-row").remove();
                var image_count = $("#product-images .image-row:not(.d-none)").length;
                if(image_count <= 0) {
                    $("#product-images .image-head").addClass("d-none");
                }
            });

            $(".remove-exists-product-image").click(function() {
                $(this).closest(".image-row").find("input.is_remove").val(1);
                $(this).closest(".image-row").addClass("d-none");
                var image_count = $("#product-images .image-row:not(.d-none)").length;
                if(image_count <= 0) {
                    $("#product-images .image-head").addClass("d-none");
                }
            });

            $(document).on('keypress', '#slug', function (e) {
                var regex = new RegExp("^[a-zA-Z0-9 \s]+$");
                var slug = $(this).val();
                console.log(slug);
                var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
                if (regex.test(str)) {
                    return true;
                }
                else {
                    e.preventDefault();
                    return false;
                }
            });

            $(document).on('keyup', '#slug', function (e) {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                setTimeout(function () {
                    var slug = $('#slug').val();
                    $.ajax({
                        url : '{{ route('admin_make-product-slug') }}',
                        method : 'post',
                        data : {_token: CSRF_TOKEN, slug : slug},
                        success : function(result){
                            var result = $.parseJSON(result);
                            $('input[name="slug"]').val(result.slug);
                        }
                    });
                }, 20);
            });
        });
    </script>
@endsection