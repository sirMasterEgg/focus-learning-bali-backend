<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ResponseBuilder;
use App\Http\Resources\Share\Auth\UserResource;
use App\Loggable;
use App\Models\User;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use Loggable;

    public function register(Request $request)
    {
        if ($request->query('token') != \Config::get('app.admin.access')) {
            return ResponseBuilder::build(
                null,
                'Unauthorized',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'name' => 'required|string|max:254'
        ]);


        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
            'name' => $request->name
        ]);

        return ResponseBuilder::build(
            new UserResource($user),
            'Success create admin',
            Response::HTTP_CREATED
        );
    }
}
