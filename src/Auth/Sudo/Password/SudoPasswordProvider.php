<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Password;

use BackedEnum;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\CurrentPasswordInput;

class SudoPasswordProvider implements SudoChallengeProvider
{
    public const string ID = 'password';

    protected bool $showResetPasswordLink = true;

    protected bool $disableIfUserHasMultiFactorAuthentication = false;

    public static function make(): static
    {
        return app(static::class);
    }

    public function isEnabled(Authenticatable $user): bool
    {
        if (blank($user->getAuthPassword())) {
            return false;
        }

        if (
            $this->disableIfUserHasMultiFactorAuthentication &&
            method_exists($user, 'hasMultiFactorAuthenticationEnabled') &&
            $user->hasMultiFactorAuthenticationEnabled()
        ) {
            return false;
        }

        return true;
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function hideResetPasswordLink(bool $condition = true): static
    {
        $this->showResetPasswordLink = ! $condition;

        return $this;
    }

    public function disableWhenUserHasMultiFactorAuthentication(bool $condition = true): static
    {
        $this->disableIfUserHasMultiFactorAuthentication = $condition;

        return $this;
    }

    public function getChallengeFormComponents(Authenticatable $user, string $authenticateAction = 'authenticate'): array
    {
        return [
            CurrentPasswordInput::make('password')
                ->label(__('profile-filament::auth/sudo/password/provider.challenge.form.password.label'))
                ->validationAttribute(__('profile-filament::auth/sudo/password/provider.challenge.form.password.validation-attribute'))
                ->hint(
                    Filament::hasPasswordReset() && $this->showResetPasswordLink
                        ? new HtmlString(Blade::render(<<<'HTML'
                        <x-filament::link :href="$url">
                            {{ __('filament-panels::auth/pages/login.actions.request_password_reset.label') }}
                        </x-filament::link>
                        HTML, ['url' => Filament::getRequestPasswordResetUrl()]))
                        : null
                )
                ->extraAlpineAttributes([
                    'x-on:keydown.enter.stop.prevent' => '$wire.' . $authenticateAction,
                ], merge: true),
        ];
    }

    public function heading(Authenticatable $user): ?string
    {
        return null;
    }

    public function icon(): null|string|BackedEnum|Htmlable
    {
        return null;
    }

    public function getChallengeSubmitLabel(): ?string
    {
        return __('profile-filament::auth/sudo/password/provider.challenge.actions.authenticate.label');
    }

    public function getChangeToProviderLabel(): string
    {
        return __('profile-filament::auth/sudo/password/provider.challenge.actions.change-to.label');
    }
}
