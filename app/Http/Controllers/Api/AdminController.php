<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponse;
use App\Http\Resources\RegisterResource;
use App\Models\User;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController
{
    public function register(Request $request)
    {
        if ($request->query('token') != \Config::get('app.admin.access')) {
            return ApiResponse::sendResponse(null, 'Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);


        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        return ApiResponse::sendResponse(new RegisterResource($user), 'Success create admin', Response::HTTP_CREATED);

    }
}
