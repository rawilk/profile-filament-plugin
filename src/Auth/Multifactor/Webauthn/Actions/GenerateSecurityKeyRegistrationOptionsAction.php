<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions;

use Filament\Facades\Filament;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\Serializer;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Support\Config;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class GenerateSecurityKeyRegistrationOptionsAction
{
    protected bool|null|ProfileFilamentPlugin $plugin = false;

    public function __invoke(
        HasWebauthn $user,
        bool $asJson = true,
    ) {
        $options = new PublicKeyCredentialCreationOptions(
            rp: $this->relatedPartyEntity(),
            user: $this->generateUserEntity($user),
            challenge: $this->challenge(),
            authenticatorSelection: $this->authenticatorSelection(),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $this->getPublicKeyCredentialDescriptors($user),
        );

        if ($asJson) {
            $options = Serializer::make()->toJson($options);
        }

        return $options;
    }

    protected function relatedPartyEntity(): PublicKeyCredentialRpEntity
    {
        return new PublicKeyCredentialRpEntity(
            name: Config::getRelyingPartyName(),
            id: Config::getRelyingPartyId(),
            icon: Config::getRelyingPartyIcon(),
        );
    }

    protected function generateUserEntity(HasWebauthn $user): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            name: $user->getPasskeyName(),
            id: $user->getPasskeyId(),
            displayName: $user->getPasskeyDisplayName(),
        );
    }

    protected function challenge(): string
    {
        return ProfileFilament::challenge();
    }

    protected function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        return new AuthenticatorSelectionCriteria(
            authenticatorAttachment: $this->getAuthenticatorAttachment(),
            userVerification: $this->getUserVerificationRequirement(),
            residentKey: $this->getResidentKeyRequirement(),
        );
    }

    /**
     * Generate a list of security keys associated with a user to prevent the same
     * key from being registered twice to a user.
     *
     * @return array<int, PublicKeyCredentialDescriptor>
     */
    protected function getPublicKeyCredentialDescriptors(HasWebauthn $user): array
    {
        return $user
            ->securityKeys()
            ->get(['id', 'user_id', 'data'])
            ->map(fn (WebauthnKey $record) => PublicKeyCredentialDescriptor::create('public-key', $record->data->publicKeyCredentialId))
            ->all();
    }

    protected function getAuthenticatorAttachment(): ?string
    {
        return $this->plugin()?->getWebauthnAuthenticatorAttachment();
    }

    protected function getUserVerificationRequirement(): string
    {
        return $this->plugin()?->getWebauthnUserVerification() ?? AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
    }

    protected function getResidentKeyRequirement(): ?string
    {
        if ($this->plugin() === null) {
            return AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED;
        }

        return $this->plugin()->getWebauthnResidentKeyRequirement();
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
