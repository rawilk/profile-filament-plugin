<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Emails;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\MustVerifyNewEmail;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\UpdateUserEmailAction;
use Rawilk\ProfileFilament\Filament\Clusters\Profile\Security;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Models\PendingUserEmail;

/**
 * @property-read bool $mustVerifyEmail
 * @property-read null|string $securityUrl
 * @property-read \Illuminate\Contracts\Auth\Authenticatable $user
 */
class UserEmail extends ProfileComponent
{
    use UsesSudoChallengeAction;
    use WithRateLimiting;

    #[Computed]
    public function mustVerifyEmail(): bool
    {
        return $this->user instanceof MustVerifyNewEmail ||
            $this->user instanceof MustVerifyEmail;
    }

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    #[Computed]
    public function securityUrl(): ?string
    {
        if (! $this->profilePlugin->isEnabled(Security::class)) {
            return null;
        }

        return $this->profilePlugin->pageUrl(Security::class);
    }

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->form([
                $this->getEmailInput(),
            ])
            ->fillForm(fn () => ['email' => null])
            ->before(function () {
                $this->ensureSudoIsActive(returnAction: 'edit');
            })
            ->using(function (Model $record, array $data, UpdateUserEmailAction $updater) {
                $updater($record, $data['email']);

                $this->clearRateLimiter('resendPendingUserEmail');
            })
            ->color('primary')
            ->record($this->user)
            ->label(__('profile-filament::pages/settings.email.actions.edit.trigger'))
            ->modalWidth('lg')
            ->modalHeading(__('profile-filament::pages/settings.email.actions.edit.modal_title'))
            ->successNotification(
                fn () => Notification::make()
                    ->success()
                    ->title(__('profile-filament::pages/settings.email.actions.edit.success_title'))
                    ->body(fn () => $this->mustVerifyEmail ? __('profile-filament::pages/settings.email.actions.edit.success_body_pending') : __('profile-filament::pages/settings.email.actions.edit.success_body'))
            )
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'edit');
            });
    }

    public function resendAction(): Action
    {
        return Action::make('resend')
            ->label(__('profile-filament::pages/settings.email.actions.resend.trigger'))
            ->action(function () {
                // Rate limiting because there really isn't a need to keep spamming the resend action.
                try {
                    $this->rateLimit(maxAttempts: 3, decaySeconds: 60 * 60, method: 'resendPendingUserEmail');
                } catch (TooManyRequestsException $exception) {
                    Notification::make()
                        ->title(__('profile-filament::pages/settings.email.actions.resend.throttled.title'))
                        ->body(
                            __('profile-filament::pages/settings.email.actions.resend.throttled.body', [
                                'seconds' => $exception->secondsUntilAvailable,
                                'minutes' => ceil($exception->secondsUntilAvailable / 60),
                            ])
                        )
                        ->danger()
                        ->send();

                    return;
                }

                if (! $pendingEmail = $this->getPendingEmail(['*'])) {
                    return;
                }

                $mailable = config('profile-filament.mail.pending_email_verification');

                Mail::to($pendingEmail->email)->send(
                    new $mailable($pendingEmail, filament()->getCurrentPanel()?->getId()),
                );

                Notification::make()
                    ->success()
                    ->title(__('profile-filament::pages/settings.email.actions.resend.success_title'))
                    ->body(__('profile-filament::pages/settings.email.actions.resend.success_body'))
                    ->send();
            })
            ->link();
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('profile-filament::pages/settings.email.actions.cancel.trigger'))
            ->link()
            ->action(function () {
                app(config('profile-filament.models.pending_user_email'))::query()
                    ->forUser($this->user)
                    ->delete();

                $this->clearRateLimiter('resendPendingUserEmail');
            })
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'cancel');
            });
    }

    public function rendering(View $view): void
    {
        $view->with([
            'pendingEmail' => $this->getPendingEmail(),
        ]);
    }

    protected function getEmailInput(): Component
    {
        return TextInput::make('email')
            ->label(__('profile-filament::pages/settings.email.actions.edit.email_label'))
            ->placeholder(__('profile-filament::pages/settings.email.actions.edit.email_placeholder', ['host' => request()?->getHost()]))
            ->helperText(fn () => $this->mustVerifyEmail ? __('profile-filament::pages/settings.email.actions.edit.email_help') : null)
            ->autofocus()
            ->autocomplete('new-email')
            ->required()
            ->email()
            ->unique(
                table: fn () => app(config('auth.providers.users.model'))->getTable(),
                column: 'email',
                ignorable: $this->user,
            );
    }

    protected function getPendingEmail(array $fields = ['id', 'email']): ?PendingUserEmail
    {
        if (! $this->user instanceof MustVerifyNewEmail) {
            return null;
        }

        return app(config('profile-filament.models.pending_user_email'))::query()
            ->forUser($this->user)
            ->latest()
            ->first($fields);
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.emails.user-email';
    }
}
