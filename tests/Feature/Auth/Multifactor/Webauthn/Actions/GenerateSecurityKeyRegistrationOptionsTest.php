<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\GenerateSecurityKeyRegistrationOptionsAction;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\ProfileFilament;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;
use Webauthn\PublicKeyCredentialCreationOptions;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'user@example.com',
        'name' => 'John Doe',
    ]);

    $this->action = Rawilk\ProfileFilament\Support\Config::getWebauthnAction('generate_security_key_registration_options', GenerateSecurityKeyRegistrationOptionsAction::class);

    ProfileFilament::generateChallengesUsing(fn () => 'fake-random-string');
});

afterEach(function () {
    ProfileFilament::generateChallengesUsing(null);
});

it('can generate options to register a security key as json', function () {
    $output = ($this->action)($this->user);

    expect($output)
        ->toBeJson()
        ->toMatchSnapshot();
});

it('can generate options to register a security key as an object', function () {
    $output = ($this->action)($this->user, asJson: false);

    expect($output)->toBeInstanceOf(PublicKeyCredentialCreationOptions::class);
});

it('can exclude previously registered credentials from the registration process', function () {
    WebauthnKey::factory()->for($this->user)->create();

    $output = ($this->action)($this->user);

    expect($output)->toMatchSnapshot();
});
