<?php

namespace App\Classes\Oauth;

class GoogleClientInstance
{
    private static ?\Google_Client $instance = null;

    public static function getInstance(): \Google_Client
    {
        if (self::$instance == null) {
            self::$instance = new \Google_Client([
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
            ]);
        }

        return self::$instance;
    }
}
