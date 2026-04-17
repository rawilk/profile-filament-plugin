<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\UpdateUserEmailAction;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\NewEmailInput;

class EditEmailAction extends Action
{
    use CanCustomizeProcess;
    use Concerns\RateLimitsResendPendingEmailChange;
    use RequiresSudoChallenge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge();

        $this->modal();

        $this->color('primary');

        $this->label(__('profile-filament::pages/settings.email.actions.edit.trigger'));

        $this->modalWidth(Width::ExtraLarge);

        $this->modalHeading(__('profile-filament::pages/settings.email.actions.edit.modal_title'));

        $this->successNotification(
            fn (): Notification => Notification::make()
                ->success()
                ->title(__('profile-filament::pages/settings.email.actions.edit.success_title'))
                ->body(
                    Filament::hasEmailChangeVerification()
                        ? __('profile-filament::pages/settings.email.actions.edit.success_body_pending')
                        : __('profile-filament::pages/settings.email.actions.edit.success_body')
                )
        );

        $this->schema([
            NewEmailInput::make('email'),
        ]);

        $this->action(function (UpdateUserEmailAction $updater, Component $livewire, $record) {
            $this->process(function (array $data, User $record, UpdateUserEmailAction $updater) {
                $updater($record, $data['email']);
            }, [
                'updater' => $updater,
                'record' => Filament::auth()->user(),
            ]);

            RateLimiter::clear($this->rateLimitKey());

            // If email verification is on and required, and if we are not using email change verification,
            // reload the page to prevent the user from doing anything else until they re-verify their email.
            if (
                (! Filament::hasEmailChangeVerification())
                && Filament::auth()->user() instanceof MustVerifyEmail
                && Filament::getCurrentOrDefaultPanel()->isEmailVerificationRequired()
                // Only run if the email verification state was reset.
                && (! Filament::auth()->user()->hasVerifiedEmail())
            ) {
                $livewire->js('window.location.reload()');

                return;
            }

            $livewire->dispatch('email-updated')->self();

            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'editEmail';
    }
}
