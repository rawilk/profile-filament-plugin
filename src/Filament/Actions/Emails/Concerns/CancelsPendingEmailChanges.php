<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails\Concerns;

use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Livewire\Component;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;

trait CancelsPendingEmailChanges
{
    use RequiresSudo;
    use WithRateLimiting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::pages/settings.email.actions.cancel.trigger'));

        $this->before(function (Component $livewire) {
            $this->ensureSudoIsActive($livewire);
        });

        $this->action(function () {
            app(config('profile-filament.models.pending_user_email'))::query()
                ->forUser(filament()->auth()->user())
                ->delete();

            $this->clearRateLimiter('resendPendingUserEmail', 'resendPendingUserEmail');
        });

        $this->mountUsing(function (Component $livewire) {
            $this->mountSudoAction($livewire);
        });

        $this->registerModalActions([
            $this->getSudoChallengeAction(),
        ]);
    }

    public static function getDefaultName(): ?string
    {
        return 'cancelPendingEmailChange';
    }
}
