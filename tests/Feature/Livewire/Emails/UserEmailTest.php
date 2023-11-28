<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Rawilk\ProfileFilament\Livewire\Emails\UserEmail;
use Rawilk\ProfileFilament\Mail\PendingEmailVerificationMail;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;
use function Rawilk\ProfileFilament\renderMarkdown;

beforeEach(function () {
    Event::fake();
    Mail::fake();

    login($this->user = User::factory()->verified()->create(['email' => 'one@example.test']));

    disableSudoMode();

    config([
        'profile-filament.models.pending_user_email' => PendingUserEmail::class,
        'profile-filament.mail.pending_email_verification' => PendingEmailVerificationMail::class,
    ]);
});

it('can be rendered', function () {
    livewire(UserEmail::class)
        ->assertSuccessful()
        ->assertSeeText('one@example.test')
        ->assertActionExists('edit');
});

it('provides a form to edit your email address', function () {
    livewire(UserEmail::class)
        ->mountAction('edit')
        ->setActionData([
            'email' => 'two@example.test',
        ])
        ->callAction('edit')
        ->assertSuccessful()
        ->assertNotified();

    expect($this->user->refresh())->email->toBe('one@example.test');

    $this->assertDatabaseHas(PendingUserEmail::class, [
        'email' => 'two@example.test',
        'user_id' => $this->user->id,
    ]);

    Mail::assertQueued(function (PendingEmailVerificationMail $mail) {
        $mail->assertTo('two@example.test');

        return true;
    });
});

it('requires sudo mode to edit your email address', function () {
    enableSudoMode();

    livewire(UserEmail::class)
        ->call('mountAction', 'edit')
        ->assertActionMounted('sudoChallenge');
});

it('shows a pending email address change in the ui', function () {
    PendingUserEmail::factory()->for($this->user)->create(['email' => 'two@example.test']);

    livewire(UserEmail::class)
        ->assertSeeText(__('profile-filament::pages/settings.email.change_pending_badge'))
        ->assertSee(renderMarkdown(__('profile-filament::pages/settings.email.pending_description', ['email' => 'two@example.test'])))
        ->assertActionVisible('cancel')
        ->assertActionVisible('resend');
});

it('requires an email address in the form', function () {
    livewire(UserEmail::class)
        ->mountAction('edit')
        ->setActionData([
            'email' => '',
        ])
        ->callAction('edit')
        ->assertHasActionErrors([
            'email' => 'required',
        ]);
});

it('requires a valid email address', function () {
    livewire(UserEmail::class)
        ->mountAction('edit')
        ->setActionData([
            'email' => 'invalid',
        ])
        ->callAction('edit')
        ->assertHasActionErrors([
            'email' => 'email',
        ]);
});

it('requires a unique email', function () {
    User::factory()->create(['email' => 'two@example.test']);

    livewire(UserEmail::class)
        ->mountAction('edit')
        ->setActionData([
            'email' => 'two@example.test',
        ])
        ->callAction('edit')
        ->assertHasActionErrors([
            'email' => 'unique',
        ]);
});

it('can cancel a pending email change', function () {
    PendingUserEmail::factory()->for($this->user)->create();

    livewire(UserEmail::class)
        ->callAction('cancel')
        ->assertSuccessful();

    $this->assertDatabaseMissing(PendingUserEmail::class, [
        'user_id' => $this->user->id,
    ]);
});

it('requires sudo mode to cancel a pending email change', function () {
    enableSudoMode();

    PendingUserEmail::factory()->for($this->user)->create();

    livewire(UserEmail::class)
        ->call('mountAction', 'cancel')
        ->assertActionMounted('sudoChallenge');

    $this->assertDatabaseHas(PendingUserEmail::class, [
        'user_id' => $this->user->id,
    ]);
});

it('can re-send the pending email verification email', function () {
    PendingUserEmail::factory()->for($this->user)->create(['email' => 'two@example.test']);

    livewire(UserEmail::class)
        ->callAction('resend')
        ->assertNotified();

    Mail::assertQueued(function (PendingEmailVerificationMail $mail) {
        $mail->assertTo('two@example.test');

        return true;
    });
});
