<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\PendingUserEmails\StoreOldUserEmailAction;
use Rawilk\ProfileFilament\Events\PendingUserEmails\NewUserEmailVerified;
use Rawilk\ProfileFilament\Mail\PendingEmailVerifiedMail;
use Rawilk\ProfileFilament\Models\OldUserEmail;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    Mail::fake();
    Event::fake();

    $this->freezeSecond();

    config([
        'profile-filament.models.pending_user_email' => PendingUserEmail::class,
        'profile-filament.models.old_user_email' => OldUserEmail::class,
        'profile-filament.actions.store_old_user_email' => StoreOldUserEmailAction::class,
        'profile-filament.mail.pending_email_verified' => PendingEmailVerifiedMail::class,
        'profile-filament.pending_email_changes.login_after_verification' => false,
        'profile-filament.pending_email_changes.login_remember' => false,
        'auth.verification.expire' => 60,
    ]);

    $this->pendingEmail = PendingUserEmail::factory()
        ->for(User::factory(state: ['email' => 'first@example.test']))
        ->create(['email' => 'new@example.test']);
});

it('verifies a pending email change', function () {
    // Links should be valid all the way up to the last second.
    $this->travel(1)->hour();

    get($this->pendingEmail->verification_url)
        ->assertSessionHas('success')
        ->assertSessionHas('verified', true)
        ->assertRedirect('/admin/login');

    $this->assertGuest();

    expect($this->pendingEmail->user->refresh())->email->toBe('new@example.test');

    $this->assertModelMissing($this->pendingEmail);

    $this->assertDatabaseHas(OldUserEmail::class, [
        'email' => 'first@example.test',
        'user_id' => $this->pendingEmail->user->getKey(),
    ]);

    Event::assertDispatched(function (NewUserEmailVerified $event) {
        expect($event->user)->toBe($this->pendingEmail->user)
            ->and($event->previousEmail)->toBe('first@example.test');

        return true;
    });

    Mail::assertQueued(function (PendingEmailVerifiedMail $mail) {
        $mail->assertTo('first@example.test');

        return true;
    });
});

it('rejects invalid tokens', function () {
    $this->pendingEmail->token = 'invalid';

    get($this->pendingEmail->verification_url)
        ->assertSessionHas('error')
        ->assertRedirect('/admin/login');

    expect($this->pendingEmail->user->refresh())->email->toBe('first@example.test');

    Event::assertNotDispatched(NewUserEmailVerified::class);
    Mail::assertNothingOutgoing();
});

it('rejects expired links', function () {
    $url = URL::signedRoute(
        'filament.admin.pending_email.verify',
        [
            'token' => $this->pendingEmail->token,
        ]
    );

    $this->travelTo(now()->addHour()->addSecond());

    get($url)
        ->assertRedirect('/admin/login');

    expect($this->pendingEmail->user->refresh())->email->toBe('first@example.test');

    $this->assertModelExists($this->pendingEmail);
});

it('can log a user in after the email is verified', function () {
    config([
        'profile-filament.pending_email_changes.login_after_verification' => true,
    ]);

    get($this->pendingEmail->verification_url)->assertRedirect('/admin');

    $this->assertAuthenticated();
});
