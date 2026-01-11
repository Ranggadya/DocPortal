<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
 
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required|email'],
            'password' => ['required']
        ]);

        if (!Auth::attempt($credentials) ) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => Auth::user(),
            'message' => 'Login Successful',
        ], 200);
    }

   public function logout(Request $request): JsonResponse
   {
    Auth::logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return response()->json([
        'message'=> 'Logout Successful',
    ], 200);
   }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()], 200);
    }
}
