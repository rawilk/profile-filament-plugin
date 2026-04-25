<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallengeWithoutModal;
use Rawilk\ProfileFilament\Support\Config;

class CancelPendingEmailChangeAction extends Action
{
    use Concerns\RateLimitsResendPendingEmailChange;
    use RequiresSudoChallengeWithoutModal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge();

        $this->label(__('profile-filament::pages/settings.email.actions.cancel.trigger'));

        $this->link();

        $this->action(function (Component $livewire) {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }

            app(Config::getModel('pending_user_email'))::query()
                ->forUser(Filament::auth()->user())
                ->delete();

            RateLimiter::clear($this->rateLimitKey());

            $livewire->dispatch('email-cancelled')->self();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'cancelPendingEmailChange';
    }
}
