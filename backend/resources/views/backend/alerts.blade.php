@if ($message = Session::get('success'))
<div class="alert alert-info alert-dismissible" role="alert">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <div class="alert-message">
        {{ $message }}
    </div>
</div>
@endif


@if ($message = Session::get('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <div class="alert-message">
        {{ $message }}
    </div>
</div>

@endif
