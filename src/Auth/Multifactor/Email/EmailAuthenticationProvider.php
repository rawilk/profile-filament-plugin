<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email;

use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Filament\Actions\DisableEmailAuthenticationAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Filament\Actions\SetupEmailAuthenticationAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Enums\RecoveryCodeSession;
use Rawilk\ProfileFilament\Contracts\EmailAuthentication\DisableEmailAuthenticationAction as DisableEmailAuthenticationContract;
use Rawilk\ProfileFilament\Contracts\EmailAuthentication\EnableEmailAuthenticationAction;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;

class EmailAuthenticationProvider implements HasBeforeChallengeHook, MultiFactorAuthenticationProvider
{
    use Concerns\VerifiesEmailAuthentication;

    public const string ID = 'email_code';

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function getSelectLabel(): string
    {
        return __('profile-filament::auth/multi-factor/email/provider.management-schema.select-label');
    }

    public function enableEmailAuthentication(HasEmailAuthentication $user): void
    {
        app(EnableEmailAuthenticationAction::class)($user);
    }

    public function disableEmailAuthentication(HasEmailAuthentication $user): void
    {
        app(DisableEmailAuthenticationContract::class)($user);
    }

    public function getManagementSchemaComponents(): array
    {
        $user = Filament::auth()->user();

        return [
            Flex::make([
                View::make('profile-filament::components.multi-factor.provider-title')
                    ->viewData(fn () => [
                        'icon' => ProfileFilamentIcon::MfaEmail->resolve(),
                        'label' => __('profile-filament::auth/multi-factor/email/provider.management-schema.label'),
                        'description' => __('profile-filament::auth/multi-factor/email/provider.management-schema.description'),
                        'configuredLabel' => __('profile-filament::auth/multi-factor/email/provider.management-schema.messages.enabled'),
                        'isEnabled' => $this->isEnabled($user),
                        'badges' => new HtmlString(Blade::render(<<<'HTML'
                        @unless ($isEnabled)
                            <x-filament::badge color="danger">
                                {{ __('profile-filament::auth/multi-factor/email/provider.management-schema.messages.disabled') }}
                            </x-filament::badge>
                        @endunless
                        HTML, ['isEnabled' => $this->isEnabled($user)])),
                    ]),

                Actions::make($this->getActions())->grow(false),
            ]),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getActions(): array
    {
        $user = Filament::auth()->user();

        return [
            SetupEmailAuthenticationAction::make('setupEmailAuthentication')
                ->provider($this)
                ->hidden(fn (): bool => $this->isEnabled($user) && RecoveryCodeSession::SettingUp->missing())
                ->after(function (Component $livewire): void {
                    $livewire->js('$wire.$refresh');
                }),

            DisableEmailAuthenticationAction::make()
                ->provider($this)
                ->visible(fn (): bool => $this->isEnabled($user))
                ->after(function (Component $livewire): void {
                    $livewire->js('$wire.$refresh');
                }),
        ];
    }

    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            Text::make(
                str(__('profile-filament::auth/multi-factor/email/provider.challenge-form.form.details.label', [
                    'email' => e($this->redactEmail($user->email)),
                ]))->inlineMarkdown()->toHtmlString()
            ),

            TextInput::make('code')
                ->label(__('profile-filament::auth/multi-factor/email/provider.challenge-form.form.code.label'))
                ->placeholder(__('profile-filament::auth/multi-factor/email/provider.challenge-form.form.code.placeholder'))
                ->validationAttribute(__('profile-filament::auth/multi-factor/email/provider.challenge-form.form.code.validation-attribute'))
                ->required()
                ->belowContent(Group::make([
                    Text::make(__('profile-filament::auth/multi-factor/email/provider.challenge-form.form.code.help', [
                        'minutes' => $this->getCodeExpiryMinutes(),
                    ])),

                    Text::make(new HtmlString(Blade::render(<<<'HTML'
                    <div class="font-semibold">
                        {{ __('profile-filament::auth/multi-factor/email/provider.challenge-form.form.problems.title') }}
                    </div>

                    <div>
                        {{ __('profile-filament::auth/multi-factor/email/provider.challenge-form.form.problems.description') }}
                    </div>
                    HTML)))
                        ->extraAttributes([
                            'class' => 'mt-4',
                        ], merge: true)
                        ->size(TextSize::ExtraSmall),

                    Action::make('resend')
                        ->label(__('profile-filament::auth/multi-factor/email/provider.challenge-form.actions.resend-code.label'))
                        ->link()
                        ->size(Size::ExtraSmall)
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
                                ->title(__('profile-filament::auth/multi-factor/email/provider.challenge-form.actions.resend-code.notifications.resent.title'))
                                ->success()
                                ->send();
                        }),
                ]))
                ->numeric()
                ->autofocus()
                ->autocomplete('one-time-code')
                ->rule(function (): Closure {
                    return function (string $attribute, $value, Closure $fail): void {
                        if ($this->verifyCode($value)) {
                            return;
                        }

                        $fail(__('profile-filament::auth/multi-factor/email/provider.challenge-form.form.code.messages.invalid'));
                    };
                }),
        ];
    }

    public function getChallengeSubmitLabel(): ?string
    {
        return __('profile-filament::auth/multi-factor/email/provider.challenge-form.actions.authenticate.label');
    }

    public function getChangeToProviderActionLabel(Authenticatable $user): ?string
    {
        return __('profile-filament::auth/multi-factor/email/provider.challenge-form.actions.change-provider.label');
    }
}
