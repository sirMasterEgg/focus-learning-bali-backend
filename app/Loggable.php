<?php

namespace App;

use Illuminate\Support\Facades\Log;

trait Loggable
{
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $this->withDefaultContext($context));
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $this->withDefaultContext($context));
    }

    protected function logError(string $message, array $context = [], ?\Throwable $throwable = null): void
    {
        Log::error($message, $this->withDefaultContext($context));
        \Sentry\captureException($throwable);
    }

    protected function logCritical(string $message, array $context = [], ?\Throwable $throwable = null): void
    {
        Log::critical($message, $this->withDefaultContext($context));
        \Sentry\captureException($throwable);
    }

    private function withDefaultContext(array $context): array
    {
        return array_merge([
            'requestId' => app('request-id'),
            'userId' => optional(auth()->user())->id,
            'url' => request()->fullUrl(),
        ], $context);
    }
}
