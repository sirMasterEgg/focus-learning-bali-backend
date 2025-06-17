<?php

namespace App\Providers;

use App\Classes\PaymentGateway\Implementation\MidtransPaymentGateway;
use App\Classes\PaymentGateway\PaymentGateway;
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
        $this->app->bind(PaymentGateway::class, MidtransPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureEmailVerification();
        $this->configureSanctum();
        $this->registerTelescope();
        /*VerifyEmail::createUrlUsing(function ($notifiable) {
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
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }*/
    }

    /**
     * Configure custom email verification URL.
     */
    private function configureEmailVerification(): void
    {
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000') . '/auth/verify';

            $verifyUrl = \URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return $frontendUrl . '?redirect=' . base64_encode($verifyUrl);
        });
    }

    /**
     * Configure Sanctum's personal access token model.
     */
    private function configureSanctum(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

    /**
     * Conditionally register Telescope services.
     */
    private function registerTelescope(): void
    {
        if ($this->app->isLocal() && $this->telescopeEnabled()) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Check if Telescope is installed and enabled.
     */
    private function telescopeEnabled(): bool
    {
        return class_exists(\Laravel\Telescope\TelescopeServiceProvider::class);
    }
}
