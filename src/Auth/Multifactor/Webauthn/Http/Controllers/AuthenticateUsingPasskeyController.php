<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Http\Controllers;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Validation\ValidationException;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Http\Requests\AuthenticateUsingPasskeyRequest;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Throwable;

class AuthenticateUsingPasskeyController
{
    public function __invoke(AuthenticateUsingPasskeyRequest $request)
    {
        if ($request->filled('_options')) {
            $this->handleCrossDomainAuthentication($request);
        }

        $this->setPanel($request->input('panel'));

        $eventBag = app(PasskeyLoginEventBagContract::class)
            ->setData($request->validated())
            ->setRequest($request)
            ->setRemember($request->boolean('remember'));

        return Pipeline::send($eventBag)
            ->through(filament(ProfileFilamentPlugin::PLUGIN_ID)->getPasskeyLoginPipes())
            ->then(fn () => app(LoginResponse::class));
    }

    protected function handleCrossDomainAuthentication(Request $request): void
    {
        try {
            ProfileFilament::verifyWebauthnNonce($request->input('nonce'));

            $decryptedOptions = Crypt::decrypt($request->input('_options'));
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'passkey' => __('profile-filament::auth/multi-factor/webauthn/passkeys.login.messages.failed'),
            ]);
        }

        WebauthnSession::AuthenticationOptions->put($decryptedOptions);
    }

    /**
     * Our passkey routes aren't bound to a panel, so we need to set the correct
     * panel to ensure the correct authentication process is used.
     */
    protected function setPanel(?string $panelId): void
    {
        $defaultPanelId = Filament::getId();

        $panelId = (string) rescue(
            fn () => filled($panelId) ? Crypt::decryptString($panelId) : $defaultPanelId,
            fn () => $defaultPanelId,
        );

        $panel = Filament::getPanel($panelId);

        Filament::setCurrentPanel($panel);
    }
}
