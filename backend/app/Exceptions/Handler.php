<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    // 👇 ADD THIS METHOD BELOW
    public function render($request, Throwable $exception)
    {
        // Redirect all 404 errors to /not-found
        if ($exception instanceof NotFoundHttpException) {
            return redirect()->route('custom.404');
        }

        return parent::render($request, $exception);
    }
}
