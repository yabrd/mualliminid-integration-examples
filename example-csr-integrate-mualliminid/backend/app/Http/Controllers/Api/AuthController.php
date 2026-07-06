<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function me()
    {
        return response()->json(['status' => 'success', 'data' => Auth::user()]);
    }

    public function logout()
    {
        return response()->json(['status' => 'success', 'message' => 'Logged out successfully']);
    }
}
