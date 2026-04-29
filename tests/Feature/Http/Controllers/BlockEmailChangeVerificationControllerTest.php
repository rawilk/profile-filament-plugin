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

it('can block an email change', function () {
    $user = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($user)->create(['email' => $newEmail]);

    $verificationUrl = ProfileFilament::getVerifyEmailChangeUrl($user, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    $blockVerificationUrl = ProfileFilament::getBlockEmailChangeVerificationUrl($user, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    // We should be able to access this link until the very last second.
    $this->travel(60)->minutes();

    actingAs($user)
        ->get($blockVerificationUrl)
        ->assertRedirect(getProfileSettingsUrl());

    Filament\Notifications\Notification::assertNotified(__('filament-panels::auth/http/controllers/block-email-change-verification-controller.notifications.blocked.title'));

    expect($user->refresh())
        ->email->not->toBe($newEmail);

    assertModelMissing($pendingEmail);

    actingAs($user)
        ->get($verificationUrl)
        ->assertForbidden();

    expect($user->refresh())
        ->email->not->toBe($newEmail);
});

it('cannot block an email change while signed in as another user', function () {
    $userToVerify = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($userToVerify)->create(['email' => $newEmail]);

    $blockVerificationUrl = ProfileFilament::getBlockEmailChangeVerificationUrl($userToVerify, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    $otherUser = User::factory()->create();

    actingAs($otherUser)
        ->get($blockVerificationUrl)
        ->assertForbidden();

    assertModelExists($pendingEmail);
});

it('cannot block an email change once it has been verified', function () {
    $userToVerify = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($userToVerify)->create(['email' => $newEmail]);

    $verificationUrl = ProfileFilament::getVerifyEmailChangeUrl($userToVerify, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    $blockVerificationUrl = ProfileFilament::getBlockEmailChangeVerificationUrl($userToVerify, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    actingAs($userToVerify)
        ->get($verificationUrl)
        ->assertRedirect(getProfileSettingsUrl());

    assertModelMissing($pendingEmail);
    expect($userToVerify->refresh())->email->toBe($newEmail);

    actingAs($userToVerify)
        ->get($blockVerificationUrl)
        ->assertRedirect(getProfileSettingsUrl());

    Filament\Notifications\Notification::assertNotified(__('filament-panels::auth/http/controllers/block-email-change-verification-controller.notifications.failed.title'));

    expect($userToVerify->refresh())->email->toBe($newEmail);
});

test('token cannot be expired', function () {
    $user = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($user)->create(['email' => $newEmail]);

    $blockVerificationUrl = ProfileFilament::getBlockEmailChangeVerificationUrl($user, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    $this->travelTo(now()->addHour()->addSecond());

    actingAs($user)
        ->get($blockVerificationUrl)
        ->assertForbidden();

    assertModelExists($pendingEmail);
});

test('block verification links cannot be replayed', function () {
    $user = User::factory()->create(['email' => 'one@example.com']);
    $newEmail = 'two@example.com';
    $pendingEmail = PendingUserEmail::factory()->for($user)->create(['email' => $newEmail]);

    $blockVerificationUrl = ProfileFilament::getBlockEmailChangeVerificationUrl($user, $newEmail, [
        'token' => $pendingEmail->token,
    ]);

    actingAs($user)
        ->get($blockVerificationUrl)
        ->assertRedirect(getProfileSettingsUrl());

    assertModelMissing($pendingEmail);

    actingAs($user)
        ->get($blockVerificationUrl)
        ->assertRedirect(getProfileSettingsUrl());

    Filament\Notifications\Notification::assertNotified(__('filament-panels::auth/http/controllers/block-email-change-verification-controller.notifications.failed.title'));
});
