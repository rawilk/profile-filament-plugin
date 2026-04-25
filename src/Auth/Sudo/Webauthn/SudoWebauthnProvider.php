<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Webauthn;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\View;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Concerns\VerifiesWebauthn;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;

class SudoWebauthnProvider implements SudoChallengeProvider
{
    use VerifiesWebauthn;

    public const string ID = 'webauthn';

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
            View::make('profile-filament::partials.multi-factor.webauthn.authenticate')
                ->viewData(fn (Component $livewire) => [
                    'promptText' => __('profile-filament::auth/sudo/webauthn/provider.challenge.form.prompt.label'),
                    'failedText' => __('profile-filament::auth/sudo/webauthn/provider.challenge.messages.failed'),
                    'livewireId' => $livewire->getId(),
                ]),

            Action::make('authenticateWebauthn')
                ->label(__('profile-filament::auth/sudo/webauthn/provider.challenge.actions.generate-options.label'))
                ->extraAttributes([
                    'class' => 'w-full',
                ])
                ->action(function (HasActions $livewire, array $arguments, Request $request) use ($user, $authenticateAction) {
                    $authenticationResponse = data_get($arguments, 'authenticationResponse');

                    if (filled($authenticationResponse)) {
                        if ($this->isValidSecurityKeyChallenge($authenticationResponse, $request, $user)) {
                            /** @var \Rawilk\ProfileFilament\Livewire\Sudo\SudoChallengeActionForm $livewire */
                            $livewire->{$authenticateAction}($request);

                            return;
                        }

                        $livewire->dispatch('webauthnAuthenticationFailed', [
                            'message' => __('profile-filament::auth/sudo/webauthn/provider.challenge.messages.failed'),
                        ]);

                        return;
                    }

                    $livewire->dispatch('webauthnAuthenticationReady', [
                        'webauthnOptions' => json_decode($this->generateAuthenticationOptions($user)),
                    ]);
                }),
        ];
    }

    public function heading(Authenticatable $user): ?string
    {
        return __('profile-filament::auth/sudo/webauthn/provider.challenge.heading');
    }

    public function icon(): null|string|BackedEnum|Htmlable
    {
        return ProfileFilamentIcon::MfaWebauthn->resolve();
    }

    public function getChallengeSubmitLabel(): ?string
    {
        return null;
    }

    public function getChangeToProviderLabel(): string
    {
        return __('profile-filament::auth/sudo/webauthn/provider.challenge.actions.change-to.label');
    }
}
