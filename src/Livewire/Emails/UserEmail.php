<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Emails;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Infolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\MustVerifyNewEmail;
use Rawilk\ProfileFilament\Filament\Actions\Emails\CancelPendingEmailChangeAction;
use Rawilk\ProfileFilament\Filament\Actions\Emails\EditEmailAction;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Security;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Models\PendingUserEmail;

/**
 * @property-read bool $mustVerifyEmail
 * @property-read null|string $securityUrl
 * @property-read User $user
 * @property-read null|PendingUserEmail $pendingEmail
 */
class UserEmail extends ProfileComponent implements HasInfolists
{
    use InteractsWithInfolists;
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

    #[Computed]
    public function pendingEmail(): ?Model
    {
        if (! $this->user instanceof MustVerifyNewEmail) {
            return null;
        }

        return app(config('profile-filament.models.pending_user_email'))::query()
            ->forUser($this->user)
            ->latest()
            ->first(['id', 'email']);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->infolist }}

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->user)
            ->schema([
                Infolists\Components\Section::make()
                    ->key('email')
                    ->heading(function (): Htmlable {
                        return new HtmlString(Blade::render(<<<'HTML'
                        <span class="flex items-center gap-x-2">
                            <span>{{ __('profile-filament::pages/settings.email.heading') }}</span>

                            @if ($pendingEmail)
                                <x-filament::badge color="warning">
                                    {{ __('profile-filament::pages/settings.email.change_pending_badge') }}
                                </x-filament::badge>
                            @endif
                        </span>
                        HTML, ['pendingEmail' => $this->pendingEmail]));
                    })
                    ->headerActions([
                        $this->getEditEmailAction()->hidden(fn (): bool => filled($this->pendingEmail?->getKey())),
                    ])
                    ->schema([
                        Infolists\Components\View::make('profile-filament::livewire.emails.pending-email-info')
                            ->viewData([
                                'pendingEmail' => $this->pendingEmail,
                                'resendAction' => $this->resendAction(),
                                'cancelAction' => $this->cancelPendingEmailChangeAction(),
                            ])
                            ->visible(fn (): bool => filled($this->pendingEmail?->getKey())),

                        $this->getEmailEntry(),

                        $this->getSecurityUrlHelpEntry(),
                    ]),
            ]);
    }

    public function resendAction(): Action
    {
        return Action::make('resend')
            ->label(__('profile-filament::pages/settings.email.actions.resend.trigger'))
            ->link()
            ->action(function () {
                if (! $this->pendingEmail) {
                    return;
                }

                // Rate limiting because there really isn't a need to keep spamming the resend action.
                try {
                    $this->rateLimit(maxAttempts: 3, decaySeconds: 60 * 60, method: 'resendPendingUserEmail', component: 'resendPendingUserEmail');
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

                $mailable = config('profile-filament.mail.pending_email_verification');

                Mail::to($this->pendingEmail->email)->send(
                    new $mailable($this->pendingEmail->refresh()->withoutRelations(), filament()->getCurrentPanel()?->getId()),
                );

                Notification::make()
                    ->success()
                    ->title(__('profile-filament::pages/settings.email.actions.resend.success_title'))
                    ->body(__('profile-filament::pages/settings.email.actions.resend.success_body'))
                    ->send();
            });
    }

    public function cancelPendingEmailChangeAction(): Action
    {
        return CancelPendingEmailChangeAction::make()
            ->link();
    }

    protected function getEditEmailAction(): Infolists\Components\Actions\Action
    {
        return EditEmailAction::make();
    }

    protected function getEmailEntry(): Infolists\Components\TextEntry
    {
        return Infolists\Components\TextEntry::make('email')
            ->label(__('profile-filament::pages/settings.email.label'))
            ->inlineLabel()
            ->helperText(__('profile-filament::pages/settings.email.email_description'));
    }

    protected function getSecurityUrlHelpEntry(): Infolists\Components\TextEntry
    {
        return Infolists\Components\TextEntry::make('help')
            ->label('')
            ->hiddenLabel()
            ->default('')
            ->formatStateUsing(
                fn (): Htmlable => new HtmlString(Blade::render(<<<'HTML'
                <div class="flex items-center gap-x-2 text-xs [&_a]:text-primary-600 dark:[&_a]:text-primary-400 [&_a:hover]:underline">
                    <div>
                        <x-filament::icon
                            alias="profile-filament::help"
                            icon="heroicon-o-question-mark-circle"
                            class="h-4 w-4"
                        />
                    </div>

                    <span>
                        {{
                            str(__('profile-filament::pages/settings.account_security_link', [
                                'url' => $url,
                            ]))
                                ->inlineMarkdown()
                                ->toHtmlString()
                        }}
                    </span>
                </div>
                HTML, ['url' => $this->securityUrl]))
            )
            ->visible(fn (): bool => filled($this->securityUrl));
    }
}
