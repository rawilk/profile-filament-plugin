<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Webauthn;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\View;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasAfterValidationCheck;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Concerns\VerifiesWebauthn;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Support\Config;

class SudoWebauthnProvider implements HasAfterValidationCheck, SudoChallengeProvider
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
            // Here to prevent user from finding the livewire component with JavaScript in the dev tools and calling
            // authenticate() themselves to bypass the challenge form.
            Hidden::make('_webauthn_challenge'),

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
                ->action(function (HasActions $livewire, array $arguments, Request $request, Action $action) use ($user, $authenticateAction) {
                    $authenticationResponse = data_get($arguments, 'authenticationResponse');

                    if (filled($authenticationResponse)) {
                        if ($this->isValidSecurityKeyChallenge($authenticationResponse, $request, $user)) {
                            $challenge = Str::random(32);

                            $action->getSchemaContainer()->fill([
                                '_webauthn_challenge' => Crypt::encryptString($challenge),
                            ]);

                            WebauthnSession::ChallengeAssertion->put($challenge);

                            /** @var \Rawilk\ProfileFilament\Auth\Sudo\Livewire\SudoChallengeActionForm $livewire */
                            $livewire->{$authenticateAction}($request);

                            return;
                        }

                        $livewire->dispatch('webauthnAuthenticationFailed', [
                            'message' => __('profile-filament::auth/sudo/webauthn/provider.challenge.messages.failed'),
                        ]);

                        return;
                    }

                    if (! ProfileFilament::plugin()->needsCrossDomainWebauthn($request->getHost())) {
                        $livewire->dispatch('webauthnAuthenticationReady', [
                            'webauthnOptions' => json_decode($this->generateAuthenticationOptions($user)),
                        ]);

                        return;
                    }

                    $url = ProfileFilament::plugin()->getCrossDomainWebauthnAuthenticationUrl(
                        user: $user,
                        originalHost: $request->getHost(),
                        data: [
                            'providerId' => $this->getId(),
                            'passkey' => false,
                            'nonce' => ProfileFilament::generateWebauthnNonce(),
                            'sudo' => true,
                        ],
                    );

                    $livewire->dispatch('webauthnExternalAuth', [
                        'url' => $url,
                        'relyingPartyId' => Config::getRelyingPartyId(),
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
