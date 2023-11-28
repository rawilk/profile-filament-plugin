<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Rawilk\ProfileFilament\Actions\PendingUserEmails\StoreOldUserEmailAction;
use Rawilk\ProfileFilament\Events\PendingUserEmails\NewUserEmailVerified;
use Rawilk\ProfileFilament\Exceptions\PendingUserEmails\InvalidVerificationLinkException;
use Rawilk\ProfileFilament\Mail\PendingEmailVerifiedMail;
use Rawilk\ProfileFilament\Models\OldUserEmail;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\VerifyEmailUser;

beforeEach(function () {
    Event::fake();
    Mail::fake();

    $this->user = User::factory()->create(['email' => 'one@example.com']);

    config([
        'profile-filament.actions.store_old_user_email' => StoreOldUserEmailAction::class,
        'profile-filament.mail.pending_email_verified' => PendingEmailVerifiedMail::class,
    ]);
});

test('query can be scoped to a user', function () {
    $otherUser = User::factory()->create(['email' => 'two@example.com']);

    PendingUserEmail::factory()
        ->for($this->user)
        ->count(2)
        ->create();
    PendingUserEmail::factory()
        ->for($otherUser)
        ->count(3)
        ->create();

    $emails = PendingUserEmail::forUser($this->user)->get();

    expect($emails)->toHaveCount(2)
        ->first()->user->toBe($this->user);
});

it('has a verification url attribute', function () {
    $pendingEmail = PendingUserEmail::factory()->for($this->user)->create(['token' => 'my_token']);

    expect($pendingEmail->verification_url)
        ->toContain('/my_token')
        ->toContain('signature')
        ->toContain('expires');
});

it('can activate itself and set a new email on its user', function () {
    $pendingEmail = PendingUserEmail::factory()->for($this->user)->create(['email' => 'other@example.com']);
    $pendingEmail->activate();

    expect($this->user->refresh())->email->toBe('other@example.com');

    $this->assertDatabaseHas(OldUserEmail::class, [
        'email' => 'one@example.com',
    ]);

    Mail::assertQueued(function (PendingEmailVerifiedMail $mail) {
        expect($mail->newEmail)->toBe('other@example.com')
            ->and($mail->oldUserEmail->email)->toBe('one@example.com');

        return true;
    });

    Event::assertDispatched(function (NewUserEmailVerified $event) {
        expect($event->user)->toBe($this->user)
            ->and($event->previousEmail)->toBe('one@example.com');

        return true;
    });

    $this->assertDatabaseMissing(PendingUserEmail::class, [
        'email' => 'other@example.com',
    ]);
});

it('will not activate itself if the email is already taken by another user', function () {
    User::factory()->create(['email' => 'two@example.com']);
    $pendingEmail = PendingUserEmail::factory()->for($this->user)->create(['email' => 'two@example.com']);

    $pendingEmail->activate();
})->throws(InvalidVerificationLinkException::class);

it('re-verifies a user email if they are an instance of MustVerifyEmail', function () {
    $user = VerifyEmailUser::factory()->create();
    $pendingEmail = PendingUserEmail::factory()->for($user, 'user')->create(['email' => 'two@example.com']);

    Date::setTestNow('2023-01-01 10:00:00');

    $pendingEmail->activate();

    Event::assertDispatched(function (NewUserEmailVerified $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });

    expect($user->refresh())
        ->email->toBe('two@example.com')
        ->email_verified_at->toDateTimeString()->toBe('2023-01-01 10:00:00');
});
