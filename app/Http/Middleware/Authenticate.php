<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Untuk aplikasi API + React, kita tidak melakukan redirect ke route login.
        // Jika belum login, biarkan Laravel mengembalikan 401.
        return null;
    }
}
