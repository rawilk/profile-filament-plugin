<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails\Concerns;

use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Livewire\Component;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\MustVerifyNewEmail;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\UpdateUserEmailAction;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;

trait EditsAuthenticatedUserEmail
{
    use RequiresSudo;
    use WithRateLimiting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->color('primary');

        $this->label(__('profile-filament::pages/settings.email.actions.edit.trigger'));

        $this->modalWidth(MaxWidth::ExtraLarge);

        $this->modalHeading(__('profile-filament::pages/settings.email.actions.edit.modal_title'));

        $this->form([
            $this->getEmailField(),
        ]);

        $this->successNotification(
            fn (User $record): Notification => Notification::make()
                ->success()
                ->title(__('profile-filament::pages/settings.email.actions.edit.success_title'))
                ->body(
                    $this->mustVerifyEmail($record)
                        ? __('profile-filament::pages/settings.email.actions.edit.success_body_pending')
                        : __('profile-filament::pages/settings.email.actions.edit.success_body')
                )
        );

        $this->before(function (Component $livewire) {
            $this->ensureSudoIsActive($livewire);
        });

        $this->action(function (Form $form, User $record, UpdateUserEmailAction $updater) {
            $updater($record, $form->getState()['email']);

            $this->clearRateLimiter('resendPendingUserEmail', 'resendPendingUserEmail');

            $this->success();
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
        return 'editEmail';
    }

    protected function getEmailField(): TextInput
    {
        return TextInput::make('email')
            ->label(__('profile-filament::pages/settings.email.actions.edit.email_label'))
            ->placeholder(__('profile-filament::pages/settings.email.actions.edit.email_placeholder', ['host' => request()?->getHost()]))
            ->helperText(
                fn (User $record): ?string => $this->mustVerifyEmail($record)
                    ? __('profile-filament::pages/settings.email.actions.edit.email_help')
                    : null
            )
            ->autocomplete('new-email')
            ->required()
            ->email()
            ->unique(
                table: fn () => app(config('auth.providers.users.model'))->getTable(),
                column: 'email',
                ignoreRecord: true,
            );
    }

    protected function mustVerifyEmail(User $record): bool
    {
        return $record instanceof MustVerifyNewEmail || $record instanceof MustVerifyEmail;
    }
}
