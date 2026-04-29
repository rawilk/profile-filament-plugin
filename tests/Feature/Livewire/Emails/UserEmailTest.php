<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Illuminate\Notifications\AnonymousNotifiable;
use Rawilk\ProfileFilament\Actions\PendingUserEmails\UpdateUserEmailAction;
use Rawilk\ProfileFilament\Livewire\Emails\UserEmail;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Notifications\Emails\NoticeOfEmailChangeRequest;
use Rawilk\ProfileFilament\Notifications\Emails\VerifyEmailChange;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    actingAs($this->user = User::factory()->create(['email' => 'email@example.test']));

    disableSudoMode();

    $this->component = UserEmail::class;
});

it('renders', function () {
    livewire($this->component)
        ->assertSuccessful()
        ->assertSeeText('email@example.test');
});

it('can edit an email address', function () {
    livewire($this->component)
        ->callAction(
            TestAction::make('editEmail')->schemaComponent(schema: 'infolist'),
            data: [
                'email' => 'new@example.test',
            ]
        )
        ->assertHasNoActionErrors();

    // The test admin panel has email change verification enabled. User record should not be modified yet.
    expect($this->user->fresh()->email)->toBe('email@example.test');

    assertDatabaseHas(PendingUserEmail::class, [
        'email' => 'new@example.test',
        'user_id' => $this->user->getKey(),
    ]);

    // User should get notified at their old email that a change is being attempted.
    Notification::assertSentTo($this->user, NoticeOfEmailChangeRequest::class, function (NoticeOfEmailChangeRequest $notification) {
        $newEmail = (fn () => $this->newEmail)->call($notification);

        expect($newEmail)->toBe('new@example.test');

        return true;
    });

    Notification::assertSentOnDemand(VerifyEmailChange::class, function (VerifyEmailChange $notification, array $channels, AnonymousNotifiable $notifiable) {
        expect($notifiable->routes['mail'])->toBe('new@example.test');

        return true;
    });
});

it('can require sudo mode to edit the email address', function () {
    enableSudoMode();

    livewire($this->component)
        ->mountAction(
            TestAction::make('editEmail')->schemaComponent(schema: 'infolist'),
        )
        ->assertActionMounted('sudoChallenge');
});

describe('validation', function () {
    test('email is required', function () {
        $this->mock(UpdateUserEmailAction::class)->shouldNotReceive('__invoke');

        livewire($this->component)
            ->callAction(
                TestAction::make('editEmail')->schemaComponent(schema: 'infolist'),
                data: [
                    'email' => null,
                ]
            )
            ->assertHasActionErrors([
                'email' => ['required'],
            ]);
    });

    it('requires a valid email address', function () {
        livewire($this->component)
            ->callAction(
                TestAction::make('editEmail')->schemaComponent(schema: 'infolist'),
                data: [
                    'email' => 'invalid',
                ]
            )
            ->assertHasActionErrors([
                'email' => ['email'],
            ]);
    });

    test('email must be unique', function () {
        User::factory()->create(['email' => 'other@example.test']);

        livewire($this->component)
            ->callAction(
                TestAction::make('editEmail')->schemaComponent(schema: 'infolist'),
                data: [
                    'email' => 'other@example.test',
                ]
            )
            ->assertHasActionErrors([
                'email' => ['unique'],
            ]);
    });
});

describe('cancel pending change', function () {
    it('can cancel a pending email change', function () {
        $record = PendingUserEmail::factory()->for($this->user)->create();

        livewire($this->component)
            ->callAction('cancelPendingEmailChange')
            ->assertHasNoActionErrors();

        assertModelMissing($record);
    });

    it('can require sudo mode to cancel a pending email change', function () {
        enableSudoMode();

        $record = PendingUserEmail::factory()->for($this->user)->create();

        livewire($this->component)
            ->callAction('cancelPendingEmailChange')
            ->assertActionMounted('sudoChallenge');

        assertModelExists($record);
    });
});

describe('re-send pending email change notification', function () {
    it('can re-send the notification', function () {
        config(['auth.verification.expire' => 60]);

        $this->freezeSecond();

        $record = PendingUserEmail::factory()->for($this->user)->create(['email' => 'new@example.test']);

        $this->travel(30)->minutes();

        livewire($this->component)
            ->callAction('resendPendingEmail', data: [
                'record' => $this->user->getKey(),
            ])
            ->assertSuccessful();

        Notification::assertSentOnDemand(VerifyEmailChange::class, function (VerifyEmailChange $notification, array $channels, AnonymousNotifiable $notifiable) {
            expect($notifiable->routes['mail'])->toBe('new@example.test');

            return true;
        });

        expect($record->refresh())->created_at->toBe(now());
    });

    it('rate limits re-sending the email', function () {
        $this->freezeSecond();

        $rateLimitKey = 'resendPendingUserEmail:' . $this->user->getKey();

        PendingUserEmail::factory()->for($this->user)->create(['email' => 'new@example.test']);

        // Simulate the rate limiter being hit
        foreach (range(1, 3) as $i) {
            RateLimiter::hit($rateLimitKey);
        }

        livewire($this->component)
            ->callAction('resendPendingEmail', data: [
                'record' => $this->user->getKey(),
            ]);

        Notification::assertSentOnDemandTimes(VerifyEmailChange::class, 0);
    });
});
