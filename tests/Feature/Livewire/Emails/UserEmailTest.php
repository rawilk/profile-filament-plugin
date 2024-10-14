<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\PendingUserEmails\UpdateUserEmailAction;
use Rawilk\ProfileFilament\Livewire\Emails\UserEmail;
use Rawilk\ProfileFilament\Mail\PendingEmailVerificationMail;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();
    Mail::fake();

    login($this->user = User::factory()->verified()->create(['email' => 'first@example.test']));

    disableSudoMode();

    config([
        'profile-filament.models.pending_user_email' => PendingUserEmail::class,
        'profile-filament.mail.pending_email_verification' => PendingEmailVerificationMail::class,
        'profile-filament.actions.update_user_email' => UpdateUserEmailAction::class,
    ]);
});

it('renders', function () {
    livewire(UserEmail::class)
        ->assertSuccessful()
        ->assertSeeText('first@example.test')
        ->assertInfolistActionExists('email', 'editEmail');
});

it('can edit a users email address', function () {
    livewire(UserEmail::class)
        ->callInfolistAction('email', 'editEmail', data: [
            'email' => 'new@example.test',
        ])
        ->assertHasNoInfolistActionErrors();

    expect($this->user->refresh())->email->toBe('first@example.test');

    $this->assertDatabaseHas(PendingUserEmail::class, [
        'email' => 'new@example.test',
        'user_id' => $this->user->getKey(),
    ]);

    Mail::assertQueued(PendingEmailVerificationMail::class);
});

it('can require sudo mode to edit the email address', function () {
    enableSudoMode();

    livewire(UserEmail::class)
        ->call('mountInfolistAction', 'editEmail', 'email', 'infolist')
        ->assertInfolistActionNotMounted('email', 'editEmail')
        ->assertSeeText(sudoChallengeTitle());
});

it('shows a pending email address change in the ui', function () {
    PendingUserEmail::factory()->for($this->user)->create(['email' => 'new@example.test']);

    livewire(UserEmail::class)
        ->assertSeeText(__('profile-filament::pages/settings.email.change_pending_badge'))
        ->assertSeeHtml(
            str(__('profile-filament::pages/settings.email.pending_description', [
                'email' => 'new@example.test',
            ]))
                ->inlineMarkdown()
                ->toHtmlString()
        )
        ->assertActionVisible('resend')
        ->assertActionVisible('cancelPendingEmailChange');
});

it('requires an email address in the form', function () {
    $this->mock(UpdateUserEmailAction::class)
        ->shouldNotReceive('__invoke');

    livewire(UserEmail::class)
        ->callInfolistAction('email', 'editEmail', data: [
            'email' => null,
        ])
        ->assertHasInfolistActionErrors([
            'email' => ['required'],
        ]);
});

it('requires a valid email address', function () {
    $this->mock(UpdateUserEmailAction::class)
        ->shouldNotReceive('__invoke');

    livewire(UserEmail::class)
        ->callInfolistAction('email', 'editEmail', data: [
            'email' => 'invalid',
        ])
        ->assertHasInfolistActionErrors([
            'email' => ['email'],
        ]);
});

it('requires a unique email', function () {
    User::factory()->create(['email' => 'other@example.test']);

    $this->mock(UpdateUserEmailAction::class)
        ->shouldNotReceive('__invoke');

    livewire(UserEmail::class)
        ->callInfolistAction('email', 'editEmail', data: [
            'email' => 'other@example.test',
        ])
        ->assertHasInfolistActionErrors([
            'email' => ['unique'],
        ]);
});

it('can cancel a pending email change', function () {
    $record = PendingUserEmail::factory()->for($this->user)->create();

    livewire(UserEmail::class)
        ->callAction('cancelPendingEmailChange')
        ->assertHasNoActionErrors();

    $this->assertModelMissing($record);
});

it('can require sudo mode to cancel a pending email change', function () {
    enableSudoMode();

    $record = PendingUserEmail::factory()->for($this->user)->create();

    livewire(UserEmail::class)
        ->call('mountAction', 'cancelPendingEmailChange')
        ->assertActionNotMounted('cancelPendingEmailChange')
        ->assertSeeText(sudoChallengeTitle());

    $this->assertModelExists($record);
});

it('can re-send the pending email verification email', function () {
    $record = PendingUserEmail::factory()->for($this->user)->create();

    livewire(UserEmail::class)
        ->callAction('resend')
        ->assertHasNoActionErrors()
        ->assertNotified();

    Mail::assertQueued(function (PendingEmailVerificationMail $mail) use ($record) {
        $mail->assertTo($record->email);

        return true;
    });
});
