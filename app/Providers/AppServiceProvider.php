<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $frontendUrl = 'http://localhost:3000/auth/verify';

            $verifyUrl = \URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(\Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return $frontendUrl . '?redirect=' . base64_encode($verifyUrl);
        });
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
