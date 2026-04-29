<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;

beforeEach(function () {
    $this->freezeSecond();

    config()->set('auth.verification.expire', 60);
});

it('can verify an email change', function () {
    $userToVerify = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($userToVerify)->create(['email' => $newEmail]);

    expect($userToVerify->refresh())
        ->email->not->toBe($newEmail);

    $verificationUrl = ProfileFilament::getVerifyEmailChangeUrl($userToVerify, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    // We should be able to access the url all the way to the last second.
    $this->travel(60)->minutes();

    actingAs($userToVerify)
        ->get($verificationUrl)
        ->assertRedirect(getProfileSettingsUrl());

    Filament\Notifications\Notification::assertNotified(__('filament-panels::auth/http/controllers/email-change-verification-controller.notifications.verified.title'));

    assertModelMissing($pendingEmail);

    expect($userToVerify->refresh())
        ->email->toBe($newEmail);
});

test('verification urls cannot be replayed', function () {
    $userToVerify = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($userToVerify)->create(['email' => $newEmail]);

    $verificationUrl = ProfileFilament::getVerifyEmailChangeUrl($userToVerify, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    actingAs($userToVerify)
        ->get($verificationUrl)
        ->assertRedirect(getProfileSettingsUrl());

    expect($userToVerify->refresh())
        ->email->toBe($newEmail);

    assertModelMissing($pendingEmail);

    actingAs($userToVerify)
        ->get($verificationUrl)
        ->assertForbidden();
});

it('requires a pending email token', function () {
    $userToVerify = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($userToVerify)->create(['email' => $newEmail]);

    $verificationUrl = ProfileFilament::getVerifyEmailChangeUrl($userToVerify, $newEmail);

    actingAs($userToVerify)
        ->get($verificationUrl)
        ->assertForbidden();

    assertModelExists($pendingEmail);

    expect($userToVerify->refresh())
        ->email->not->toBe($newEmail);
});

it('forbids expired tokens', function () {
    $userToVerify = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($userToVerify)->create(['email' => $newEmail]);

    $verificationUrl = ProfileFilament::getVerifyEmailChangeUrl($userToVerify, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    $this->travelTo(now()->addHour()->addSecond());

    actingAs($userToVerify)
        ->get($verificationUrl)
        ->assertForbidden();
});

it('cannot verify an email when signed in as another user', function () {
    $userToVerify = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($userToVerify)->create(['email' => $newEmail]);

    $verificationUrl = ProfileFilament::getVerifyEmailChangeUrl($userToVerify, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    $otherUser = User::factory()->create();

    actingAs($otherUser)
        ->get($verificationUrl)
        ->assertForbidden();

    expect($userToVerify->refresh())
        ->email->not->toBe($newEmail);
});
