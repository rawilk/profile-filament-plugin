<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Support\SudoChallengeProviders;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Rawilk\ProfileFilament\Dto\SudoChallengeAssertions\SudoChallengeAssertion;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\CurrentPasswordInput;
use SensitiveParameter;

class PasswordProvider implements SudoChallengeProvider
{
    public static function allowedFor(?User $user = null): bool
    {
        return filled($user?->getAuthPassword());
    }

    public static function submitIsHidden(?User $user = null): bool
    {
        return false;
    }

    public static function submitLabel(?User $user = null): string
    {
        return __('profile-filament::messages.sudo_challenge.password.submit');
    }

    public static function heading(?User $user = null): ?string
    {
        return null;
    }

    public static function icon(): ?string
    {
        return null;
    }

    public static function linkLabel(?User $user = null): string
    {
        return __('profile-filament::messages.sudo_challenge.password.use_label');
    }

    public static function slug(): string
    {
        return 'password';
    }

    public static function schema(Component $livewire): array
    {
        return [
            CurrentPasswordInput::make('password')
                ->id("{$livewire->getId()}.sudo.password")
                ->label(__('profile-filament::messages.sudo_challenge.password.input_label'))
                ->hint(
                    Filament::hasPasswordReset()
                        ? new HtmlString(Blade::render(<<<'HTML'
                        <x-filament::link :href="$url">
                            {{ __('filament-panels::auth/pages/login.actions.request_password_reset.label') }}
                        </x-filament::link>
                        HTML, ['url' => Filament::getRequestPasswordResetUrl()]))
                        : null
                )
                ->extraAlpineAttributes([
                    'x-on:keydown.enter.stop.prevent' => '$wire.confirm',
                ]),
        ];
    }

    public static function assert(
        #[SensitiveParameter] array $data,
        ?User $user,
        Request $request,
        #[SensitiveParameter] ?array $extra = null,
    ): SudoChallengeAssertion {
        // Since we are using Laravel's current_password validation rule, there is no need to do any checking here.
        // If we get to this point with this provider, the assertion is valid.
        return SudoChallengeAssertion::make(isValid: true);
    }
}
