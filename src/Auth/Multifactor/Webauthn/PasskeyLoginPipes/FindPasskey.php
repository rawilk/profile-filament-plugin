<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\PasskeyLoginPipes;

use Closure;
use Illuminate\Validation\ValidationException;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\FindSecurityKeyToAuthenticateAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUsed;
use Rawilk\ProfileFilament\Support\Config;

class FindPasskey
{
    public function __invoke(PasskeyLoginEventBagContract $request, Closure $next)
    {
        $findPasskeyAction = Config::getWebauthnAction('find_security_key_to_authenticate', FindSecurityKeyToAuthenticateAction::class);

        $passkey = $findPasskeyAction(
            publicKeyCredentialJson: data_get($request->getData(), 'passkeyResponse'),
            securityKeyOptionsJson: WebauthnSession::AuthenticationOptions->get(),
            requiresPasskey: true,
        );

        if (! $passkey?->user) {
            $this->throwFailureException();
        }

        $request
            ->setPasskey($passkey)
            ->setUser($passkey->user);

        WebauthnKeyUsed::dispatch($passkey->user, $passkey, $request->getRequest());

        return $next($request);
    }

    protected function throwFailureException(): never
    {
        throw ValidationException::withMessages([
            'passkey' => __('profile-filament::auth/multi-factor/webauthn/passkeys.login.messages.failed'),
        ]);
    }
}
