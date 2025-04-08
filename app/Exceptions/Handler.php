<?php

namespace App\Exceptions;

use Throwable;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Override unauthenticated response for Sanctum.
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Authentication failed. Please login again.'
        ], JsonResponse::HTTP_UNAUTHORIZED);
    }


    public function render($request, Throwable $exception): JsonResponse
    {
        // Handle Model Not Found Exception
        if ($exception instanceof ModelNotFoundException) {
            return ResponseHelper::withError('Resource not found.', [], 404);
        }

        return parent::render($request, $exception);
    }
}
