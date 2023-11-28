<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
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

    config([
        'profile-filament.models.pending_user_email' => PendingUserEmail::class,
        'profile-filament.models.old_user_email' => OldUserEmail::class,
        'profile-filament.actions.store_old_user_email' => StoreOldUserEmailAction::class,
        'profile-filament.mail.pending_email_verified' => PendingEmailVerifiedMail::class,
        'profile-filament.pending_email_changes.login_after_verification' => false,
        'profile-filament.pending_email_changes.login_remember' => true,
        'auth.verification.expire' => 60,
    ]);
});

it('verifies a pending email change', closure: function () {
    Date::setTestNow('2023-01-01 10:00:00');

    $user = User::factory()->create(['email' => 'old@example.test']);
    $pendingEmail = PendingUserEmail::factory()->for($user)->create(['email' => 'new-email@example.test']);

    $url = $pendingEmail->verification_url;

    $this->travelTo(now()->addHour());

    get($url)
        ->assertSessionHas('success')
        ->assertSessionHas('verified', true)
        ->assertRedirect('/admin/login');

    expect(auth()->check())->toBeFalse()
        ->and($user->refresh())->email->toBe('new-email@example.test');

    $this->assertDatabaseHas(OldUserEmail::class, [
        'email' => 'old@example.test',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseMissing(PendingUserEmail::class, [
        'email' => 'new-email@example.test',
    ]);

    Event::assertDispatched(NewUserEmailVerified::class);

    Mail::assertQueued(function (PendingEmailVerifiedMail $mail) {
        $mail->assertTo('old@example.test');

        return true;
    });
});

it('rejects invalid tokens', function () {
    $user = User::factory()->create(['email' => 'old@example.test']);
    $pendingEmail = PendingUserEmail::factory()->for($user)->create(['email' => 'new-email@example.test']);
    $pendingEmail->token = 'foo';

    get($pendingEmail->verification_url)
        ->assertSessionHas('error')
        ->assertRedirect('/admin/login');

    expect($user->fresh())->email->toBe('old@example.test');

    Event::assertNotDispatched(NewUserEmailVerified::class);
});

it('blocks expired links', function () {
    Date::setTestNow('2023-01-01 10:00:00');
    $user = User::factory()->create(['email' => 'old@example.test']);
    $pendingEmail = PendingUserEmail::factory()->for($user)->create(['email' => 'new-email@example.test']);

    $url = $pendingEmail->verification_url;

    $this->travelTo(now()->addHour()->addSecond());

    get($url)
        ->assertForbidden();

    expect($user->refresh())->email->toBe('old@example.test');

    $this->assertDatabaseHas(PendingUserEmail::class, [
        'email' => 'new-email@example.test',
    ]);
});

it('can log a user in after they verify their new email', function () {
    config([
        'profile-filament.pending_email_changes.login_after_verification' => true,
    ]);

    $user = User::factory()->create(['email' => 'old@example.test']);
    $pendingEmail = PendingUserEmail::factory()->for($user)->create(['email' => 'new-email@example.test']);

    get($pendingEmail->verification_url);

    expect(auth()->check())->toBeTrue();
});
