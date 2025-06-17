<?php

namespace App\Http\Controllers\Api\Public;

use App\Classes\Oauth\GoogleClientInstance;
use App\Classes\ResponseBuilder;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Share\Auth\UserResource;
use App\Loggable;
use App\Mail\ResetPasswordOtpMail;
use App\Models\PersonalAccessToken;
use App\Models\ResetCodePassword;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mail;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use Loggable;

    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'email' => $request->email,
                'name' => $request->name,
                'password' => Hash::make($request->password),
            ]);

            $this->logInfo('User registered successfully', [
                'name' => $user->name,
                'email' => $user->email,
            ]);

            $user->refresh();
            $user->sendEmailVerificationNotification();

            return ResponseBuilder::build(
                new UserResource($user),
                'Successfully registered',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            $this->logError('Error during user registration', ['exception' => $e]);
            return ResponseBuilder::build(
                null,
                'Failed to register user',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            $this->logWarning('Login attempt failed', [
                'email' => $request->email,
            ]);
            return ResponseBuilder::build(
                null,
                'Invalid credentials',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            return ResponseBuilder::build(
                null,
                'Email not verified',
                Response::HTTP_FORBIDDEN
            );
        }

        $token = $user->createToken('auth_token', abilities: [$user->role], expiresAt: Carbon::now()->addMonth())->plainTextToken;

        $this->logInfo('User logged in successfully', [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return ResponseBuilder::build(
            [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'Successfully logged in'
        );
    }

    public function loginGoogle(GoogleLoginRequest $request)
    {
        $googleInstance = GoogleClientInstance::getInstance();
        $token = $request->input('id_token');

        $payload = $googleInstance->verifyIdToken($token);

        if (!$payload || !isset($payload)) {
            $this->logWarning('Google login failed - Invalid token');

            return ResponseBuilder::build(
                null,
                'Invalid token',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user = User::whereHas('userOauth', function ($query) use ($payload) {
            $query->where('provider', 'google')
                ->where('provider_user_id', $payload['sub']);
        })->first();


        if (!$user || !isset($user)) {
            try {
                $user = \DB::transaction(function () use ($payload) {
                    $user = User::create([
                        'email' => $payload['email'],
                        'name' => $payload['name'],
                        'email_verified_at' => Carbon::now(),
                    ]);

                    $user->userOauth()->create([
                        'provider' => 'google',
                        'provider_user_id' => $payload['sub'],
                    ]);

                    $this->logInfo('User created from Google login', [
                        'name' => $user->name,
                        'email' => $user->email,
                    ]);

                    return $user;
                });
            } catch (\Throwable $e) {
                $this->logError('Failed to create user from Google login', ['exception' => $e]);

                return ResponseBuilder::build(
                    null,
                    'Failed to create user',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        $token = $user->createToken('auth_token', expiresAt: Carbon::now()->addMonth())->plainTextToken;

        $this->logInfo('User logged in with Google', [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return ResponseBuilder::build([
            'user' => new UserResource($user),
            'token' => $token,
        ],
            'Successfully logged in'
        );
    }

    public function logout()
    {
        $user = Auth::user();
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $user->tokens()->where('id', $token->id)->delete();
        }

        $this->logInfo('User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return ResponseBuilder::build(
            null,
            null,
            Response::HTTP_NO_CONTENT
        );
    }

    public function verifyConfirmEmail(string $id, Request $request)
    {
        if (!$request->hasValidSignature()) {
            $this->logWarning('Invalid or expired email verification link', ['user_id' => $id]);
            return ResponseBuilder::build(
                null,
                'Invalid signature/Expired url',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = User::findOrFail($id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            $this->logInfo('User email verified', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        return ResponseBuilder::build(
            null,
            null,
            Response::HTTP_NO_CONTENT
        );
    }

    public function resendConfirmEmail()
    {
        $user = Auth::user();
        if ($user->hasVerifiedEmail()) {
            $this->logWarning('Email already verified', ['user_id' => $user->id, 'email' => $user->email]);

            return ResponseBuilder::build(
                null,
                'Email already verified',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->sendEmailVerificationNotification();

        $this->logInfo('Email verification link resent', ['user_id' => $user->id, 'email' => $user->email]);

        return ResponseBuilder::build(
            null,
            'Email verification link has been sent to your email'
        );
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $email = $request->input('email');
        $otp = mt_rand(100000, 999999);

        ResetCodePassword::where('email', $email)->delete();

        $resetPassword = ResetCodePassword::create([
            'email' => $email,
            'code' => $otp,
        ]);

        $this->logInfo('Password reset code generated', [
            'email' => $resetPassword->email,
            'code' => \Str::mask($resetPassword->code, '*', 1, 4),
        ]);

        Mail::to($resetPassword->email)->send(new ResetPasswordOtpMail($resetPassword->code));

        return ResponseBuilder::build(
            null,
            'Reset code has been sent to your email'
        );
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $passwordReset = ResetCodePassword::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$passwordReset) {
            $this->logWarning('Invalid password reset code', [
                'email' => $request->email,
                'code' => \Str::mask($request->code, '*', 1, 4),
            ]);

            return ResponseBuilder::build(
                null,
                'Invalid reset code',
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($passwordReset->created_at > now()->addHour()) {
            $this->logWarning('Password reset code expired', [
                'email' => $passwordReset->email,
                'code' => \Str::mask($passwordReset->code, '*', 1, 4),
            ]);

            $passwordReset->delete();
            return ResponseBuilder::build(
                null,
                'Reset code has been expired',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = User::firstWhere('email', $passwordReset->email);

        if (!$user) {
            return ResponseBuilder::build(
                null,
                'User not found',
                Response::HTTP_NOT_FOUND
            );
        }

        $user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        $passwordReset->delete();

        $this->logInfo('Password reset successful', [
            'email' => $user->email,
        ]);

        return ResponseBuilder::build(
            null,
            'Successfully reset password'
        );
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();

        if ($user->password && !Hash::check($request->old_password, $user->password)) {
            return ResponseBuilder::build(
                null,
                'Invalid old password',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $this->logInfo('Password changed successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return ResponseBuilder::build(
            null,
            'Successfully changed password'
        );
    }

    public function setPassword(SetPasswordRequest $request)
    {
        $user = Auth::user();

        if ($user->password) {
            return ResponseBuilder::build(
                null,
                'Password already set',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $this->logInfo('Password set successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);


        return ResponseBuilder::build(
            null,
            'Successfully set password'
        );
    }

    public function user()
    {
        $user = Auth::user();

        $this->logInfo('User retrieved successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return ResponseBuilder::build(
            new UserResource($user),
            'Successfully retrieved user'
        );
    }
}
