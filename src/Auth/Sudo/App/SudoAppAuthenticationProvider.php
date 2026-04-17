<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\App;

use BackedEnum;
use Closure;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Concerns\VerifiesOneTimeCodes;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;

class SudoAppAuthenticationProvider implements SudoChallengeProvider
{
    use VerifiesOneTimeCodes;

    public const string ID = 'app';

    public function __construct(protected Google2FA $google2FA)
    {
    }

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
            OneTimeCodeInput::make('code')
                ->label(__('profile-filament::auth/sudo/app/provider.challenge.form.code.label'))
                ->validationAttribute(__('profile-filament::auth/sudo/app/provider.challenge.form.code.validation-attribute'))
                ->required()
                ->autofocus()
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, $value, Closure $fail) use ($user): void {
                        if ($this->isValidCodeForAnApp($value, $user)) {
                            return;
                        }

                        $fail(__('profile-filament::auth/sudo/app/provider.challenge.form.code.messages.invalid'));
                    };
                }),
        ];
    }

    public function heading(Authenticatable $user): ?string
    {
        return __('profile-filament::auth/sudo/app/provider.challenge.heading');
    }

    public function icon(): null|string|BackedEnum|Htmlable
    {
        return FilamentIcon::resolve(ProfileFilamentIcon::MfaTotp->value) ?? Heroicon::OutlinedDevicePhoneMobile;
    }

    public function getChallengeSubmitLabel(): ?string
    {
        return __('profile-filament::auth/sudo/app/provider.challenge.actions.authenticate.label');
    }

    public function getChangeToProviderLabel(): string
    {
        return __('profile-filament::auth/sudo/app/provider.challenge.actions.change-to.label');
    }
}
