<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\EmailAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;

class DisableEmailAuthenticationAction extends Action
{
    use RequiresSudoChallenge;

    protected null|Closure|EmailAuthenticationProvider $provider = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge();

        $this->label(__('profile-filament::auth/multi-factor/email/actions/disable.label'));

        $this->size(Size::Small);

        $this->color('danger');

        $this->requiresConfirmation();

        $this->modalIcon(Heroicon::OutlinedLockOpen);

        $this->modalSubmitActionLabel(__('profile-filament::auth/multi-factor/email/actions/disable.modal.actions.submit.label'));

        $this->modalHeading(__('profile-filament::auth/multi-factor/email/actions/disable.modal.heading'));

        $this->modalDescription(__('profile-filament::auth/multi-factor/email/actions/disable.modal.description'));

        $this->action(function (): void {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }

            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication $user */
            $user = Filament::auth()->user();

            DB::transaction(function () use ($user): void {
                $this->getProvider()->disableEmailAuthentication($user);
            });

            Notification::make()
                ->title(__('profile-filament::auth/multi-factor/email/actions/disable.notifications.disabled.title'))
                ->success()
                ->icon(Heroicon::OutlinedLockOpen)
                ->send();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'disableEmailAuthentication';
    }

    public function provider(null|Closure|EmailAuthenticationProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): EmailAuthenticationProvider
    {
        $provider = $this->evaluate($this->provider);

        if (! ($provider instanceof EmailAuthenticationProvider)) {
            throw new LogicException('A [' . EmailAuthenticationProvider::class . '] instance must be provided to the [' . static::class . ']');
        }

        return $provider;
    }
}
