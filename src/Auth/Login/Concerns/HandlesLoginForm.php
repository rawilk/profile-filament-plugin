<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\Concerns;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\Facades\Pipeline;
use Rawilk\ProfileFilament\Auth\Login\Dto\LoginEventBag;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @mixin \Filament\Auth\Pages\Login
 */
trait HandlesLoginForm
{
    // Handle login according to my own personal preferences.
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $eventBag = app(LoginEventBag::class)
            ->setData($this->form->getState())
            ->setRequest(request());

        return Pipeline::send($eventBag)
            ->through(filament(ProfileFilamentPlugin::PLUGIN_ID)->getLoginPipes())
            ->then(fn () => app(LoginResponse::class));
    }
}
