<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Http\Controllers;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\Facades\Pipeline;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Http\Requests\AuthenticateUsingPasskeyRequest;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class AuthenticateUsingPasskeyController
{
    public function __invoke(AuthenticateUsingPasskeyRequest $request)
    {
        $eventBag = app(PasskeyLoginEventBagContract::class)
            ->setData($request->validated())
            ->setRequest($request)
            ->setRemember($request->boolean('remember'));

        return Pipeline::send($eventBag)
            ->through(filament(ProfileFilamentPlugin::PLUGIN_ID)->getPasskeyLoginPipes())
            ->then(fn () => app(LoginResponse::class));
    }
}
