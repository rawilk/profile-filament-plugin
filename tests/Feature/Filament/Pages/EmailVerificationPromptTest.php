<?php

declare(strict_types=1);

use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Rawilk\ProfileFilament\Filament\Pages\EmailVerificationPrompt;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\be;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->page = EmailVerificationPrompt::class;
});

it('renders', function () {
    $userToVerify = User::factory()->unverified()->create();

    be($userToVerify);

    get(Filament::getEmailVerificationPromptUrl())
        ->assertSuccessful();
});

it('can resend the notification', function () {
    Notification::fake();

    $userToVerify = User::factory()->unverified()->create();

    be($userToVerify);

    livewire($this->page)
        ->callAction('resendNotification')
        ->assertNotified();

    Notification::assertSentTo($userToVerify, VerifyEmail::class);
});

it('redirects guests to the panel', function () {
    $this->withoutMiddleware(Authenticate::class);

    $panel = Filament::getCurrentOrDefaultPanel();

    get(Filament::getEmailVerificationPromptUrl())
        ->assertRedirect($panel->getUrl());
});

describe('rate limiting', function () {
    it('can throttle resend notification attempts', function () {
        Notification::fake();

        $userToVerify = User::factory()->unverified()->create();

        be($userToVerify);

        foreach (range(1, 2) as $i) {
            livewire($this->page)
                ->callAction('resendNotification')
                ->assertNotified();
        }

        Notification::assertSentToTimes($userToVerify, VerifyEmail::class, times: 2);

        livewire($this->page)
            ->callAction('resendNotification')
            ->assertNotified();

        Notification::assertSentToTimes($userToVerify, VerifyEmail::class, times: 2);
    });

    it('can throttle resend attempts per user', function () {
        Notification::fake();

        $userToVerify = User::factory()->unverified()->create();

        be($userToVerify);

        // Clear the IP-based rate limiter between attempts to isolate the
        // user-based limit (simulates an attacker rotating IPs).
        $clearIpRateLimiter = function (): void {
            RateLimiter::clear('livewire-rate-limiter:' . sha1(EmailVerificationPrompt::class . '|resendNotification|' . request()->ip()));
        };

        foreach (range(1, 2) as $i) {
            $clearIpRateLimiter();

            livewire($this->page)
                ->callAction('resendNotification')
                ->assertNotified();
        }

        Notification::assertSentToTimes($userToVerify, VerifyEmail::class, times: 2);

        $clearIpRateLimiter();

        // The 3rd attempt should be rate limited by user ID.
        livewire($this->page)
            ->callAction('resendNotification')
            ->assertNotified();

        Notification::assertSentToTimes($userToVerify, VerifyEmail::class, times: 2);
    });
});
