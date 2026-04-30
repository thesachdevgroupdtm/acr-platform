<style>
    .image_errortext{
        color:red;
        font-size:10px;
        white-space: nowrap;
    }
</style>
<div class="modal-header">
    <h5 class="modal-title">{{ isset($record->id) ? __('Edit') : __('Add')}}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form method="POST" action="@if(isset($record->id)){{ route('admin_fuel-type-update', array('id' => Crypt::encrypt($record->id))) }}@else{{route('admin_fuel-type-store')}}@endif" id="page-form" enctype="multipart/form-data" data-parsley-validate="">
    {{ csrf_field() }}
    <div class="modal-body m-3" id="form-detail">
        <input type="hidden" name="id" id="id" value="{{ isset($record->id) ? Crypt::encrypt($record->id) : '' }}">
        <div class="form-row">
            <div class="mb-3 col-md-6">
                <label class="form-label" for="title">{{__('Title')}}<span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" placeholder="{{__('Title')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->title) ? $record->title : old('title') }}">
            </div>
            <?php /**<div class="mb-3 col-md-6">
                <label class="form-label" for="image">{{__('Image')}}<span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="image" name="image" placeholder="{{__('Image')}}" required=""  data-parsley-required-message="{{ __("This value is required.")}}" value="{{ isset($record->image) ? $record->image : old('image') }}">
            </div>**/ ?>
            <div class="mb-3 col-md-12">
                <label class="form-label" for="image">{{__('Image')}}<span class="text-danger">*</span></label>
                <div class="profile-icon">
                    @if(isset($record->image))
                        @if($record->image !='')
                            @php($required = '')
                            <img class='previewImage img-fluid' id="uploadPreview0" src="{{url('public/uploads/fueltype/'.$record->image)}}"  alt=''>
                        @else
                            @php($required = 'required')
                            <img class='img-fluid' id="uploadPreview0" src="{{url('public/no.jpg')}}"  alt=''>
                        @endif
                    @else
                        @php($required = 'required')
                        <img class='img-fluid' id="uploadPreview0" src="{{url('public/no.jpg')}}"  alt=''>
                    @endif
                </div>
                <div class="m-b-10">
                    <input type="file" id="uploadImage0" accept="image/x-png, image/gif, image/jpeg" class="btn btn-warning btn-block btn-sm"  name="image" {{$required}} data-parsley-required-message="{{ __("This value is required.")}}" onChange="this.parentNode.nextSibling.value = this.value; PreviewImage(0);" >
                </div> 
                <p class="image_errortext">For Best resolution please upload 92*59 size and in WebP file format.</p>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary">{{__('Submit')}}</button>
    </div>
</form>

