<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Rawilk\ProfileFilament\Actions\PendingUserEmails\UpdateUserEmailAction;
use Rawilk\ProfileFilament\Mail\PendingEmailVerificationMail;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\BasicUser;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\VerifyEmailUser;

beforeEach(function () {
    Event::fake();
    Mail::fake();
    Notification::fake();

    config([
        'profile-filament.models.pending_user_email' => PendingUserEmail::class,
        'profile-filament.mail.pending_email_verification' => PendingEmailVerificationMail::class,
    ]);
});

it('updates a user email address', function () {
    $user = BasicUser::factory()->create(['email' => 'original@example.test']);

    app(UpdateUserEmailAction::class)($user, 'new@example.test');

    expect($user->refresh())->email->toBe('new@example.test');

    Mail::assertNotQueued(PendingEmailVerificationMail::class);
    Notification::assertNothingSentTo($user);
});

it('stores a pending email change for MustVerifyNewEmail users', function () {
    $user = User::factory()->create(['email' => 'original@example.test']);

    app(UpdateUserEmailAction::class)($user, 'new@example.test');

    expect($user->refresh())->email->toBe('original@example.test');

    $this->assertDatabaseHas(PendingUserEmail::class, [
        'email' => 'new@example.test',
        'user_id' => $user->id,
    ]);

    Mail::assertQueued(function (PendingEmailVerificationMail $mail) {
        expect($mail->pendingUserEmail->email)->toBe('new@example.test')
            ->and($mail->panelId)->toBe('admin');

        $mail->assertTo('new@example.test');

        return true;
    });

    // Make sure the VerifyEmail notification is not being sent as well.
    Notification::assertNotSentTo($user, VerifyEmail::class);
});

it('invalidates an email verification for MustVerifyEmail users', function () {
    $user = VerifyEmailUser::factory()->verified()->create(['email' => 'original@example.test']);

    app(UpdateUserEmailAction::class)($user, 'new@example.test');

    expect($user->refresh())
        ->email->toBe('new@example.test')
        ->email_verified_at->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);
});
