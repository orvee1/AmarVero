<?php

namespace App\Support\Security;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RateLimitGuard
{
    public function ensureAllowed(string $key, int $maxAttempts, int $decaySeconds, string $field, string $message): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                $field => __($message, ['seconds' => $seconds]),
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    public function reset(string $key): void
    {
        RateLimiter::clear($key);
    }
}
