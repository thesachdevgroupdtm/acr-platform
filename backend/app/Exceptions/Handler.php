<?php

namespace App\Exceptions;

use App\Services\Order\DuplicateBookingException;
use App\Services\Order\PhoneNotVerifiedException;
use App\Services\Order\RateLimitedException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Phase 2.5a — checkout / order domain exceptions land as
        // typed JSON responses instead of leaking 500s. Each maps to
        // the HTTP code documented in /PHASE2_CONTRACT.md §5.4.
        $this->renderable(function (PhoneNotVerifiedException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
            }
        });

        $this->renderable(function (RateLimitedException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], Response::HTTP_TOO_MANY_REQUESTS);
            }
        });

        $this->renderable(function (DuplicateBookingException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });
    }
}
