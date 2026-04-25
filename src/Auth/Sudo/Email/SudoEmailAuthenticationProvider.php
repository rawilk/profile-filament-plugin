<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Email;

use BackedEnum;
use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\RateLimiter;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Concerns\VerifiesEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;

class SudoEmailAuthenticationProvider implements HasBeforeChallengeHook, SudoChallengeProvider
{
    use VerifiesEmailAuthentication;

    public const string ID = 'email_code';

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function getChallengeFormComponents(Authenticatable $user, string $authenticateAction = 'authenticate'): array
    {
        return [
            Text::make(
                str(__('profile-filament::auth/sudo/email/provider.challenge.form.details.label', [
                    'email' => e($this->redactEmail($user->email)),
                ]))->inlineMarkdown()->toHtmlString()
            ),

            TextInput::make('code')
                ->label(__('profile-filament::auth/sudo/email/provider.challenge.form.code.label'))
                ->placeholder(__('profile-filament::auth/sudo/email/provider.challenge.form.code.placeholder'))
                ->validationAttribute(__('profile-filament::auth/sudo/email/provider.challenge.form.code.validation-attribute'))
                ->required()
                ->numeric()
                ->autofocus()
                ->autocomplete('one-time-code')
                ->belowContent(
                    Action::make('resend')
                        ->label(__('profile-filament::auth/sudo/email/provider.challenge.actions.resend-code.label'))
                        ->link()
                        ->icon(Heroicon::ChevronRight)
                        ->iconPosition(IconPosition::After)
                        ->action(function () use ($user): void {
                            if (! $this->sendCode($user)) {
                                $this->getThrottledNotification(
                                    new TooManyRequestsException(
                                        static::class,
                                        'resend',
                                        request()->ip(),
                                        RateLimiter::availableIn($this->getSendCodeRateLimitKey($user)),
                                    )
                                )?->send();

                                return;
                            }

                            Notification::make()
                                ->title(__('profile-filament::auth/sudo/email/provider.challenge.actions.resend-code.notifications.resent.title'))
                                ->success()
                                ->send();
                        })
                )
                ->rule(function (): Closure {
                    return function (string $attribute, $value, Closure $fail): void {
                        if ($this->verifyCode($value)) {
                            return;
                        }

                        $fail(__('profile-filament::auth/sudo/email/provider.challenge.form.code.messages.invalid'));
                    };
                }),
        ];
    }

    public function heading(Authenticatable $user): ?string
    {
        return __('profile-filament::auth/sudo/email/provider.challenge.heading');
    }

    public function icon(): null|string|BackedEnum|Htmlable
    {
        return ProfileFilamentIcon::MfaEmail->resolve();
    }

    public function getChallengeSubmitLabel(): ?string
    {
        return __('profile-filament::auth/sudo/email/provider.challenge.actions.authenticate.label');
    }

    public function getChangeToProviderLabel(): string
    {
        return __('profile-filament::auth/sudo/email/provider.challenge.actions.change-to.label');
    }
}
