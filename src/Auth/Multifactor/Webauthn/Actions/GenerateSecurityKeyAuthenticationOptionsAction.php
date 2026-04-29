<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions;

use Filament\Facades\Filament;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\Serializer;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Support\Config;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class GenerateSecurityKeyAuthenticationOptionsAction
{
    protected bool|null|ProfileFilamentPlugin $plugin = false;

    public function __invoke(
        bool $isPasskey = false,
        ?HasWebauthn $user = null,
    ): string {
        $options = $this->getOptions($isPasskey, $user);

        $options = Serializer::make()->toJson($options);

        WebauthnSession::AuthenticationOptions->put($options);

        return $options;
    }

    protected function getOptions(bool $isPasskey, ?HasWebauthn $user): PublicKeyCredentialRequestOptions
    {
        if ($isPasskey) {
            return $this->passkeyOptions();
        }

        return new PublicKeyCredentialRequestOptions(
            challenge: $this->generateChallenge(),
            rpId: Config::getRelyingPartyId(),
            allowCredentials: filled($user) ? $this->getPublicKeyCredentialDescriptors($user) : [],
            userVerification: $this->getUserVerificationRequirement(),
        );
    }

    protected function passkeyOptions(): PublicKeyCredentialRequestOptions
    {
        return new PublicKeyCredentialRequestOptions(
            challenge: $this->generateChallenge(),
            rpId: Config::getRelyingPartyId(),
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        );
    }

    protected function generateChallenge(): string
    {
        return ProfileFilament::challenge();
    }

    /**
     * @return array<PublicKeyCredentialDescriptor>
     */
    protected function getPublicKeyCredentialDescriptors(HasWebauthn $user): array
    {
        return $user
            ->securityKeys()
            ->get(['id', 'user_id', 'data'])
            ->map(fn (WebauthnKey $record) => PublicKeyCredentialDescriptor::create('public-key', $record->data->publicKeyCredentialId))
            ->all();
    }

    protected function getUserVerificationRequirement(): string
    {
        return $this->plugin()?->getWebauthnUserVerification() ?? AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
    }

    protected function plugin(): ?ProfileFilamentPlugin
    {
        if ($this->plugin !== false) {
            return $this->plugin;
        }

        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $panel->hasPlugin(ProfileFilamentPlugin::PLUGIN_ID)) {
            return $this->plugin = null;
        }

        return $this->plugin = $panel->getPlugin(ProfileFilamentPlugin::PLUGIN_ID);
    }
}
