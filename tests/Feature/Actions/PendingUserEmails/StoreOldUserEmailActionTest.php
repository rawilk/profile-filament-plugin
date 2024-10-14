<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Rawilk\ProfileFilament\Actions\PendingUserEmails\StoreOldUserEmailAction;
use Rawilk\ProfileFilament\Mail\PendingEmailVerifiedMail;
use Rawilk\ProfileFilament\Models\OldUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config([
        'profile-filament.models.old_user_email' => OldUserEmail::class,
        'profile-filament.mail.pending_email_verified' => PendingEmailVerifiedMail::class,
    ]);

    Mail::fake();

    $this->user = User::factory()->create();
});

it('stores a reference to an old email address for a user', function () {
    app(StoreOldUserEmailAction::class)($this->user, 'old@example.test');

    $this->assertDatabaseHas(OldUserEmail::class, [
        'email' => 'old@example.test',
        'user_id' => $this->user->getKey(),
    ]);

    Mail::assertQueued(function (PendingEmailVerifiedMail $mail) {
        expect($mail->oldUserEmail->email)->toBe('old@example.test')
            ->and($mail->newEmail)->toBe($this->user->email);

        $mail->assertTo('old@example.test');

        return true;
    });
});

it('only stores a reference to an old email once for a user', function () {
    OldUserEmail::factory()->for($this->user)->count(2)->create(['email' => 'old@example.test']);

    expect(OldUserEmail::where('email', 'old@example.test')->count())->toBe(2);

    app(StoreOldUserEmailAction::class)($this->user, 'old@example.test');

    expect(OldUserEmail::where('email', 'old@example.test')->count())->toBe(1);
});
