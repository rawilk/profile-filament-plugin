<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\View;
use Hash;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use LogicException;
use Rawilk\FilamentPasswordInput\Password;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Actions\RegenerateRecoveryCodesAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\RecoveryProvider;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Events\RecoveryCodeWasUsed;
use SensitiveParameter;

class RecoveryCodeProvider implements RecoveryProvider
{
    /**
     * Are users allowed to regenerate recovery codes?
     */
    protected bool $canRegenerate = true;

    /**
     * The number of codes to generate for a user.
     */
    protected int $count = 8;

    protected ?Closure $generateCodesUsingCallback = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function isEnabled(HasMultiFactorAuthenticationRecovery $user): bool
    {
        return filled($user->getAuthenticationRecoveryCodes());
    }

    public function needsToBeSetup(HasMultiFactorAuthenticationRecovery $user): bool
    {
        return blank($user->getAuthenticationRecoveryCodes());
    }

    public function getManagementSchemaComponents(): array
    {
        return [
            Flex::make([
                View::make('profile-filament::components.multi-factor.provider-title')
                    ->key('recovery-provider-title')
                    ->viewData([
                        'icon' => ProfileFilamentIcon::MfaRecoveryCodes->resolve(),
                        'label' => __('profile-filament::auth/multi-factor/recovery/provider.management-schema.label'),
                        'description' => __('profile-filament::auth/multi-factor/recovery/provider.management-schema.description'),
                        'badges' => $this->getCodesRemainingBadge(),
                    ]),

                Actions::make($this->getActions())->grow(false),
            ]),
        ];
    }

    /**
     * @return array<string>
     */
    public function getRecoveryCodes(HasMultiFactorAuthenticationRecovery $user): array
    {
        $codes = $user->getAuthenticationRecoveryCodes();

        if (blank($codes)) {
            throw new LogicException('The user does not have any recovery codes to use.');
        }

        return $codes;
    }

    /**
     * @return array<Action>
     */
    public function getActions(): array
    {
        /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication|HasMultiFactorAuthenticationRecovery $user */
        $user = Filament::auth()->user();

        return [
            RegenerateRecoveryCodesAction::make()
                ->provider($this)
                ->disabled(fn (): bool => ! $user->hasMultiFactorAuthenticationEnabled())
                ->tooltip(
                    fn (): ?string => $user->hasMultiFactorAuthenticationEnabled()
                        ? null
                        : __('profile-filament::auth/multi-factor/recovery/provider.management-schema.messages.needs-mfa-enabled'),
                ),
        ];
    }

    public function generateRecoveryCodes(): array
    {
        return Collection::times(
            $this->getCodeCount(),
            fn (): string => $this->generateRecoveryCode(),
        )->all();
    }

    public function generateRecoveryCode(): string
    {
        if ($this->generateCodesUsingCallback) {
            return call_user_func($this->generateCodesUsingCallback);
        }

        // Code format: XXXX-XXXX-XXXX-XXXX
        return Str::of(Str::random(16))
            ->upper()
            ->split(4)
            ->implode('-');
    }

    public function saveRecoveryCodes(HasMultiFactorAuthenticationRecovery $user, ?array $codes): void
    {
        if (! is_array($codes)) {
            $user->saveAuthenticationRecoveryCodes(null);

            return;
        }

        $user->saveAuthenticationRecoveryCodes(array_map(
            fn (string $code): string => Hash::make($code),
            $codes,
        ));
    }

    public function verifyRecoveryCode(#[SensitiveParameter] string $recoveryCode, ?HasMultiFactorAuthenticationRecovery $user = null): bool
    {
        /** @var HasMultiFactorAuthenticationRecovery $user */
        $user ??= Filament::auth()->user();

        $remainingCodes = [];
        $isValid = false;

        foreach ($this->getRecoveryCodes($user) as $hashedRecoveryCode) {
            if (Hash::check($recoveryCode, $hashedRecoveryCode)) {
                $isValid = true;

                continue;
            }

            $remainingCodes[] = $hashedRecoveryCode;
        }

        if ($isValid) {
            $user->saveAuthenticationRecoveryCodes($remainingCodes);

            RecoveryCodeWasUsed::dispatch($user);
        }

        return $isValid;
    }

    public function regenerableCodes(bool $condition = true): static
    {
        $this->canRegenerate = $condition;

        return $this;
    }

    public function codeCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function generateCodesUsing(?Closure $callback): static
    {
        $this->generateCodesUsingCallback = $callback;

        return $this;
    }

    public function canRegenerateCodes(): bool
    {
        return $this->canRegenerate;
    }

    public function getCodeCount(): int
    {
        return $this->count;
    }

    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            Password::make('recoveryCode')
                ->label(__('profile-filament::auth/multi-factor/recovery/provider.challenge-form.code.label'))
                ->validationAttribute(__('profile-filament::auth/multi-factor/recovery/provider.challenge-form.code.validation-attribute'))
                ->live(onBlur: true)
                ->required()
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                        if (blank($value)) {
                            return;
                        }

                        if (is_string($value) && $this->verifyRecoveryCode($value, $user)) {
                            return;
                        }

                        $fail(__('profile-filament::auth/multi-factor/recovery/provider.challenge-form.code.messages.invalid'));
                    };
                }),
        ];
    }

    public function getChallengeSubmitLabel(): ?string
    {
        return __('profile-filament::auth/multi-factor/recovery/provider.challenge-form.actions.authenticate.label');
    }

    public function getChangeToProviderActionLabel(Authenticatable $user): ?string
    {
        return __('profile-filament::auth/multi-factor/recovery/provider.challenge-form.actions.change-provider.label');
    }

    protected function getCodesRemainingBadge(): ?Htmlable
    {
        /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication|HasMultiFactorAuthenticationRecovery $user */
        $user = Filament::auth()->user();
        $recoveryCodes = $user->getAuthenticationRecoveryCodes() ?? [];

        if (! $user->hasMultiFactorAuthenticationEnabled()) {
            return null;
        }

        return new HtmlString(Blade::render(<<<'HTML'
        <x-filament::badge color="gray">
            {{ Lang::choice('profile-filament::auth/multi-factor/recovery/provider.management-schema.messages.codes-remaining', $recoveryCodes, ['count' => $recoveryCodes]) }}
        </x-filament::badge>
        HTML, [
            'recoveryCodes' => count($recoveryCodes),
        ]));
    }
}
