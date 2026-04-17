<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

trait ThrowsFailedEvents
{
    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        event(app(Failed::class, [
            'guard' => property_exists($guard, 'name') ? $guard->name : '',
            'user' => $user,
            'credentials' => $credentials,
        ]));
    }

    protected function throwFailureValidationException(string $validationKey = 'data.email'): never
    {
        throw ValidationException::withMessages([
            $validationKey => __('auth.failed'),
        ]);
    }
}
