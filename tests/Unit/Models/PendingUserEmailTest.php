<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Rawilk\ProfileFilament\Actions\PendingUserEmails\StoreOldUserEmailAction;
use Rawilk\ProfileFilament\Events\PendingUserEmails\NewUserEmailVerified;
use Rawilk\ProfileFilament\Exceptions\PendingUserEmails\InvalidVerificationLinkException;
use Rawilk\ProfileFilament\Mail\PendingEmailVerifiedMail;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\VerifyEmailUser;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'one@example.com']);

    Event::fake();
    Mail::fake();

    config([
        'profile-filament.actions.store_old_user_email' => StoreOldUserEmailAction::class,
        'profile-filament.mail.pending_email_verified' => PendingEmailVerifiedMail::class,
        'auth.verification.expire' => 60,
    ]);
});

test('query can be scoped to a user', function () {
    $otherUser = User::factory()->create(['email' => 'two@example.com']);

    $userRecords = PendingUserEmail::factory()
        ->for($this->user)
        ->count(2)
        ->create();

    PendingUserEmail::factory()
        ->for($otherUser)
        ->count(3)
        ->create();

    $records = PendingUserEmail::forUser($this->user)->get();

    expect($records)->toHaveCount(2)
        ->modelsMatchExactly($userRecords);
});

it('has a verification url attribute', function () {
    $this->freezeSecond();

    $record = PendingUserEmail::factory()->for($this->user)->create(['token' => 'my_token']);

    expect($record->verification_url)
        ->toContain('/my_token')
        ->toContain('signature')
        ->toContain('expires=' . now()->addMinutes(60)->unix());
});

it('can activate itself and set a new email on its user', function () {
    $record = PendingUserEmail::factory()->for($this->user)->create(['email' => 'new@example.com']);
    $record->activate();

    expect($this->user->refresh())->email->toBe('new@example.com');

    Mail::assertQueued(function (PendingEmailVerifiedMail $mail) {
        expect($mail->newEmail)->toBe('new@example.com')
            ->and($mail->oldUserEmail->email)->toBe('one@example.com');

        return true;
    });

    Event::assertDispatched(function (NewUserEmailVerified $event) {
        expect($event->user)->toBe($this->user)
            ->and($event->previousEmail)->toBe('one@example.com');

        return true;
    });

    $this->assertModelMissing($record);
});

it('prevents taken emails from being activated', function () {
    User::factory()->create(['email' => 'two@example.com']);
    $record = PendingUserEmail::factory()->for($this->user)->create(['email' => 'two@example.com']);

    $record->activate();
})->throws(InvalidVerificationLinkException::class);

it('re-verifies a user email if they are an instance of MustVerifyEmail', function () {
    $user = VerifyEmailUser::factory()->create();
    $record = PendingUserEmail::factory()->for($user, 'user')->create(['email' => 'two@example.com']);

    Date::setTestNow('2024-01-01 10:00:00');

    $record->activate();

    Event::assertDispatched(function (NewUserEmailVerified $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });

    expect($user->refresh())
        ->email->toBe('two@example.com')
        ->email_verified_at->toBe(now());
});
