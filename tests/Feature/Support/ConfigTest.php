<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions as WebauthnActions;
use Rawilk\ProfileFilament\Support\Config as PackageConfig;

it('can get the model classes', function () {
    expect(PackageConfig::getAuthenticatableModel())->not()->toBeNull()
        ->and(PackageConfig::getModel('webauthn_key'))->not()->toBeNull()
        ->and(PackageConfig::getModel('authenticator_app'))->not()->toBeNull()
        ->and(PackageConfig::getModel('pending_user_email'))->not()->toBeNull();
});

it('can get the default relying party configuration', function () {
    expect(PackageConfig::getRelyingPartyName())->not()->toBeNull()
        ->and(PackageConfig::getRelyingPartyId())->not()->toBeNull()
        ->and(PackageConfig::getRelyingPartyIcon())->toBeNull();
});

it('can get the default webauthn action classes', function (string $actionName, string $actionBaseClass) {
    expect(PackageConfig::getWebauthnActionClass($actionName, $actionBaseClass))->not->toBeNull()->toBe($actionBaseClass);
})->with([
    ['configure_ceremony_step_manager_factory', WebauthnActions\ConfigureCeremonyStepManagerFactoryAction::class],
    ['delete_security_key', WebauthnActions\DeleteSecurityKeyAction::class],
    ['find_security_key_to_authenticate', WebauthnActions\FindSecurityKeyToAuthenticateAction::class],
    ['generate_security_key_authentication_options', WebauthnActions\GenerateSecurityKeyAuthenticationOptionsAction::class],
    ['generate_security_key_registration_options', WebauthnActions\GenerateSecurityKeyRegistrationOptionsAction::class],
    ['store_security_key', WebauthnActions\StoreSecurityKeyAction::class],
]);

it('can indicate if the package should hash user passwords', function () {
    config()->set('profile-filament.hash_user_passwords', false);

    expect(PackageConfig::hashUserPasswords())->toBeFalse();
});

it('can get the sudo expiration', function () {
    expect(PackageConfig::getSudoExpiration())->toBeInstanceOf(DateInterval::class);
});
