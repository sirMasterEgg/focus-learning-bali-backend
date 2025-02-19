<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponse;
use App\Classes\GoogleClientInstance;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetCodePasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\RegisterResource;
use App\Mail\SendResetPasswordCode;
use App\Models\PersonalAccessToken;
use App\Models\ResetCodePassword;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Mail;
use Symfony\Component\HttpFoundation\Response;

class AuthController
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'email' => $request->email,
            'name' => $request->name,
            'password' => Hash::make($request->password),
        ]);

        $user->sendEmailVerificationNotification();

        return ApiResponse::sendResponse([new RegisterResource($user)], 'Successfully registered', Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return ApiResponse::sendResponse(null, 'Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            return ApiResponse::sendResponse(null, 'Email not verified', Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken('auth_token', expiresAt: Carbon::now()->addMonth())->plainTextToken;

        return ApiResponse::sendResponse([
            'user' => $user,
            'token' => $token,
        ], 'Successfully logged in');

    }

    public function loginGoogle(GoogleLoginRequest $request)
    {
        $googleInstance = GoogleClientInstance::getInstance();
        $token = $request->input('id_token');

        $payload = $googleInstance->verifyIdToken($token);

        if (!$payload || !isset($payload)) {
            return ApiResponse::sendResponse(null, 'Invalid token', Response::HTTP_UNAUTHORIZED);
        }

        $user = User::whereHas('userOauth', function ($query) use ($payload) {
            $query->where('provider', 'google')
                ->where('provider_user_id', $payload['sub']);
        })->first();


        if (!$user || !isset($user)) {
            $user = \DB::transaction(function () use ($payload) {
                $user = User::create([
                    'email' => $payload['email'],
                    'name' => $payload['name'],
                    'avatar' => $payload['picture'],
                    'email_verified_at' => Carbon::now(),
                ]);

                $user->userOauth()->create([
                    'provider' => 'google',
                    'provider_user_id' => $payload['sub'],
                ]);

                return $user;
            });
        }

        $token = $user->createToken('auth_token', expiresAt: Carbon::now()->addMonth())->plainTextToken;

        return ApiResponse::sendResponse([
            'user' => $user,
            'token' => $token,
        ], 'Successfully logged in');
    }

    public function logout()
    {
        $token = Auth::user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            Auth::user()->tokens()->where('id', $token->id)->delete();
        }

        return ApiResponse::sendResponse(null, null, Response::HTTP_NO_CONTENT);
    }

    public function verifyConfirmEmail(string $id, Request $request)
    {
        if (!$request->hasValidSignature()) {
            return ApiResponse::sendResponse(null, 'Invalid signature/Expired url', Response::HTTP_BAD_REQUEST);
        }

        $user = User::findOrFail($id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return ApiResponse::sendResponse(null, null, Response::HTTP_NO_CONTENT);
    }

    public function resendConfirmEmail()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return ApiResponse::sendResponse(null, 'Email already verified', Response::HTTP_BAD_REQUEST);
        }

        Auth::user()->sendEmailVerificationNotification();

        return ApiResponse::sendResponse(null, 'Email verification link has been sent to your email');
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validated = [
            'email' => $request->input('email'),
        ];

        ResetCodePassword::where('email', $validated['email'])->delete();

        $validated['code'] = mt_rand(100000, 999999);
        $codeData = ResetCodePassword::create($validated);

        Mail::to($validated['email'])->send(new SendResetPasswordCode($codeData->code));

        return ApiResponse::sendResponse(null, 'Reset code has been sent to your email');
    }

    public function verifyOtp(ResetCodePasswordRequest $request)
    {
        $passwordReset = ResetCodePassword::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$passwordReset) {
            return ApiResponse::sendResponse(null, 'Invalid reset code', Response::HTTP_BAD_REQUEST);
        }

        if ($passwordReset->created_at > now()->addHour()) {
            $passwordReset->delete();
            return ApiResponse::sendResponse(null, 'Reset code has been expired', Response::HTTP_BAD_REQUEST);
        }

        $user = User::firstWhere('email', $passwordReset->email);

        if (!$user) {
            return ApiResponse::sendResponse(null, 'User not found', Response::HTTP_NOT_FOUND);
        }

        $user->update($request->only('password'));

        $passwordReset->delete();

        return ApiResponse::sendResponse(null, 'Successfully reset password');
    }

    public function changePassword()
    {
        return ApiResponse::sendResponse(null, 'Successfully changed password');
    }

    public function user()
    {
        return ApiResponse::sendResponse(Auth::user(), 'Successfully retrieved user');
    }
}
