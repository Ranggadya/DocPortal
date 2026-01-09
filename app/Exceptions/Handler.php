<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Render unauthenticated exception.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Untuk API: kembalikan 401 JSON, jangan redirect
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Fallback (kalau suatu hari pakai web)
        return redirect()->guest('/login');
    }
}
