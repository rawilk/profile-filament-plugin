<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums\Livewire;

enum MfaEvent: string
{
    // Authenticator Apps
    case AppAdded = 'mfa:authenticator-app-added';
    case AppDeleted = 'mfa:authenticator-app-deleted';
    case HideAppForm = 'mfa:hide-authenticator-app-form';
    case ShowAppForm = 'mfa:show-authenticator-app-form';
    case HideAppList = 'mf:hide-authenticator-app-list';

    // Webauthn Keys
    case ToggleWebauthnKeys = 'mfa:toggle-webauthn-keys';
    case WebauthnFormInitialized = 'mfa:webauthn-form-initialized';
    case WebauthnKeyAdded = 'mfa:webauthn-key-added';
    case WebauthnKeyDeleted = 'mfa:webauthn-key-deleted';
    case WebauthnAssertionInitialized = 'mfa:webauthn-assertion-initialized';
    case WebauthnKeyUpgradedToPasskey = 'mfa:webauthn-key:upgraded';

    // Passkeys
    case PasskeyRegistered = 'passkey:registered';
    case PasskeyDeleted = 'passkey:deleted';
    case StartPasskeyUpgrade = 'passkey:start-upgrade';
}
