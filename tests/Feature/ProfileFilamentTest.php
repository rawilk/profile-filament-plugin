<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\EmailAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Email\SudoEmailAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Password\SudoPasswordProvider;
use Rawilk\ProfileFilament\ProfileFilament;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->service = new ProfileFilament;
});

describe('user timezone', function () {
    afterEach(function () {
        ProfileFilament::$findUserTimezoneUsingCallback = null;
    });

    it('can get the timezone for a user', function () {
        $user = User::factory()->make(['timezone' => 'America/Chicago']);

        expect($this->service->userTimezone($user))->toBe('America/Chicago');
    });

    it('returns a default timezone if the user does not have one set', function () {
        $user = User::factory()->make(['timezone' => null]);

        expect($this->service->userTimezone($user))->toBe('UTC');
    });

    test('a custom callback can be used for getting a user timezone', function () {
        ProfileFilament::findUserTimezoneUsing(fn ($user): string => $user->email);

        $user = User::factory()->make(['email' => 'email@example.test']);

        expect($this->service->userTimezone($user))->toBe('email@example.test');
    });
});

describe('verify email change url', function () {
    afterEach(function () {
        ProfileFilament::createVerifyEmailChangeUrlUsing(null);
    });

    it('generates a url for email verification', function () {
        $url = $this->service->getVerifyEmailChangeUrl($user = User::factory()->create(), 'email@example.com');

        expect($url)->toContain('/email-change-verification/verify/' . $user->getKey())
            ->toContain('signature=');
    });

    test('a custom callback can be used to generate the url', function () {
        ProfileFilament::createVerifyEmailChangeUrlUsing(fn ($user) => 'foo/' . $user->email);

        $url = $this->service->getVerifyEmailChangeUrl($user = User::factory()->create(), 'email@example.com');

        expect($url)->toBe('foo/' . $user->email);
    });
});

describe('block email change verification url', function () {
    afterEach(function () {
        ProfileFilament::createBlockEmailChangeUrlUsing(null);
    });

    it('generates a url for email verification', function () {
        $url = $this->service->getBlockEmailChangeVerificationUrl($user = User::factory()->create(), 'email@example.com');

        expect($url)->toContain('/verify/' . $user->getKey())
            ->toContain('signature=')
            ->toContain('/block');
    });

    test('a custom callback can be used to generate the url', function () {
        ProfileFilament::createBlockEmailChangeUrlUsing(fn ($user) => 'foo/' . $user->email);

        $url = $this->service->getBlockEmailChangeVerificationUrl($user = User::factory()->create(), 'email@example.com');

        expect($url)->toBe('foo/' . $user->email);
    });
});

describe('email verification url', function () {
    afterEach(function () {
        ProfileFilament::createEmailVerificationUrlUsing(null);
    });

    it('generates a url for email verification', function () {
        $url = $this->service->getEmailVerificationUrl($user = User::factory()->create());

        expect($url)->toContain('/verify/' . $user->getKey())
            ->toContain('signature=');
    });

    test('a custom callback can be used to generate the url', function () {
        ProfileFilament::createEmailVerificationUrlUsing(fn ($user) => 'foo/' . $user->email);

        $url = $this->service->getEmailVerificationUrl($user = User::factory()->create());

        expect($url)->toBe('foo/' . $user->email);
    });
});

describe('preferred providers', function () {
    it('gets a preferred mfa provider for a user', function () {
        $user = User::factory()->make(['preferred_mfa_provider' => 'email_code']);

        $providers = collect([
            AppAuthenticationProvider::make(),
            EmailAuthenticationProvider::make(),
        ]);

        expect($this->service->preferredMfaProviderFor($user, $providers))->toBe('email_code');
    });

    it('falls back to the first available provider if preferred provider is not enabled', function () {
        $user = User::factory()->make(['preferred_mfa_provider' => 'email_code']);

        $providers = collect([
            WebauthnProvider::make(),
            AppAuthenticationProvider::make(),
        ]);

        expect($this->service->preferredMfaProviderFor($user, $providers))->toBe('webauthn');
    });

    it('returns the first provider id if no preference is set', function () {
        $user = User::factory()->make(['preferred_mfa_provider' => null]);

        $providers = collect([
            WebauthnProvider::make(),
            AppAuthenticationProvider::make(),
            EmailAuthenticationProvider::make(),
        ]);

        expect($this->service->preferredMfaProviderFor($user, $providers))->toBe('webauthn');
    });

    it("uses a user's preferred mfa provider to get the initial sudo provider", function () {
        $user = User::factory()->make(['preferred_mfa_provider' => 'email_code']);

        $providers = collect([
            SudoEmailAuthenticationProvider::make(),
            SudoPasswordProvider::make(),
        ]);

        expect($this->service->preferredSudoChallengeProviderFor($user, $providers))->toBe('email_code');
    });
});

describe('webauthn challenge', function () {
    afterEach(function () {
        ProfileFilament::generateChallengesUsing(null);
    });

    it('generates a challenge for webauthn requests', function () {
        $challenge = $this->service->challenge(length: 16);

        expect($challenge)->toHaveLength(16);
    });

    test('a custom callback can be used to generate webauthn challenges', function () {
        ProfileFilament::generateChallengesUsing(fn (): string => 'custom-challenge');

        $challenge = $this->service->challenge();

        expect($challenge)->toBe('custom-challenge');
    });
});
