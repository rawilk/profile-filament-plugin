<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn;

use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\View;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\DeleteSecurityKeyAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\GenerateSecurityKeyRegistrationOptionsAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\StoreSecurityKeyAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Actions\SetupSecurityKeyAction;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Support\Config;

class WebauthnProvider implements MultiFactorAuthenticationProvider
{
    use Concerns\VerifiesWebauthn;

    public const string ID = 'webauthn';

    /**
     * The number of passkeys or security keys the user may register.
     */
    protected int $deviceRegistrationLimit = 5;

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
        return __('profile-filament::auth/multi-factor/webauthn/provider.management-schema.select-label');
    }

    public function getManagementSchemaComponents(): array
    {
        /** @var Contracts\HasWebauthn $user */
        $user = Filament::auth()->user();

        return [
            Flex::make([
                View::make('profile-filament::components.multi-factor.provider-title')
                    ->viewData(fn () => [
                        'icon' => ProfileFilamentIcon::MfaWebauthn->resolve(),
                        'label' => __('profile-filament::auth/multi-factor/webauthn/provider.management-schema.label'),
                        'description' => __('profile-filament::auth/multi-factor/webauthn/provider.management-schema.description'),
                        'configuredLabel' => __('profile-filament::auth/multi-factor/webauthn/provider.management-schema.messages.configured'),
                        'isEnabled' => $this->isEnabled($user),
                    ]),

                Actions::make($this->getActions())->grow(false),
            ]),

            View::make('profile-filament::partials.multi-factor.webauthn.security-key-list')
                ->visible(fn (): bool => $user->securityKeys->isNotEmpty())
                ->viewData([
                    'securityKeys' => $user->securityKeys,
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
            SetupSecurityKeyAction::make()
                ->provider(fn () => $this)
                ->label(fn (): string => $this->isEnabled($user) ? __('profile-filament::auth/multi-factor/webauthn/actions/set-up.another-label') : __('profile-filament::auth/multi-factor/webauthn/actions/set-up.label'))
                ->hidden(fn (): bool => $this->deviceLimitHasBeenReached($user))
                ->after(function (Component $livewire): void {
                    $livewire->js('$wire.$refresh');
                }),
        ];
    }

    public function generateRegistrationOptions(): string
    {
        /** @var Authenticatable&Contracts\HasWebauthn $user */
        $user = Filament::auth()->user();

        $generateSecurityKeyOptionsAction = Config::getWebauthnAction(
            'generate_security_key_registration_options',
            GenerateSecurityKeyRegistrationOptionsAction::class,
        );

        $options = $generateSecurityKeyOptionsAction($user);

        WebauthnSession::RegistrationOptions->put($options);

        return $options;
    }

    public function storeSecurityKey(string $securityKeyJson, array $data, string $hostName): void
    {
        /** @var Authenticatable&Contracts\HasWebauthn $user */
        $user = Filament::auth()->user();

        $storeSecurityKeyAction = Config::getWebauthnAction(
            'store_security_key',
            StoreSecurityKeyAction::class,
        );

        $storeSecurityKeyAction(
            user: $user,
            securityKeyJson: $securityKeyJson,
            securityKeyOptionsJson: WebauthnSession::RegistrationOptions->pull(),
            hostName: $hostName,
            additionalData: $data,
        );
    }

    public function deleteSecurityKey(WebauthnKey $securityKey): void
    {
        $deleteAction = Config::getWebauthnAction('delete_security_key', DeleteSecurityKeyAction::class);

        $deleteAction($securityKey);
    }

    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            View::make('profile-filament::partials.multi-factor.webauthn.authenticate')
                ->viewData(fn (Component $livewire) => [
                    'promptText' => __('profile-filament::auth/multi-factor/webauthn/provider.challenge-form.form.prompt.label'),
                    'failedText' => __('profile-filament::auth/multi-factor/webauthn/provider.challenge-form.messages.failed'),
                    'livewireId' => $livewire->getId(),
                ]),

            Action::make('authenticateWebauthn')
                ->label(__('profile-filament::auth/multi-factor/webauthn/provider.challenge-form.actions.authenticate.label'))
                ->extraAttributes([
                    'class' => 'w-full',
                ])
                ->action(function (HasActions $livewire, array $arguments, Request $request) use ($user) {
                    $authenticationResponse = data_get($arguments, 'authenticationResponse');

                    if (filled($authenticationResponse)) {
                        if ($this->isValidSecurityKeyChallenge($authenticationResponse, $request, $user)) {
                            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Filament\MultiFactorChallenge $livewire */
                            return $livewire->authenticate();
                        }

                        $livewire->dispatch('webauthnAuthenticationFailed', [
                            'message' => __('profile-filament::auth/multi-factor/webauthn/provider.challenge-form.messages.failed'),
                        ]);

                        return;
                    }

                    $livewire->dispatch('webauthnAuthenticationReady', [
                        'webauthnOptions' => json_decode($this->generateAuthenticationOptions($user)),
                    ]);
                }),
        ];
    }

    public function getChallengeSubmitLabel(): ?string
    {
        return null;
    }

    public function getChangeToProviderActionLabel(Authenticatable $user): ?string
    {
        return __('profile-filament::auth/multi-factor/webauthn/provider.challenge-form.actions.change-provider.label');
    }

    public function limitRegistrationsTo(int $limit): static
    {
        $this->deviceRegistrationLimit = $limit;

        return $this;
    }

    public function getDeviceRegistrationLimit(): int
    {
        return $this->deviceRegistrationLimit;
    }

    public function deviceLimitHasBeenReached(Authenticatable $user): bool
    {
        $limit = $this->getDeviceRegistrationLimit();
        if ($limit < 1) {
            return true;
        }

        return $user->securityKeys->count() >= $limit;
    }
}
