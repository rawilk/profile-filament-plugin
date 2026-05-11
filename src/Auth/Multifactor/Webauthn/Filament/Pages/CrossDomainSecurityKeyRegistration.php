<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Js;
use Illuminate\Support\Uri;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Throwable;

/**
 * @property-read null|ProfileFilamentPlugin $plugin
 * @property-read null|MultiFactorAuthenticationProvider $providerInstance
 * @property-read UserProvider $userProvider
 * @property-read Authenticatable $user
 */
class CrossDomainSecurityKeyRegistration extends SimplePage
{
    #[Locked]
    public string $userId;

    #[Locked]
    public string $providerId;

    #[Locked]
    public string $origin;

    #[Computed]
    public function plugin(): ?ProfileFilamentPlugin
    {
        return ProfileFilament::plugin();
    }

    #[Computed]
    public function providerInstance(): ?MultiFactorAuthenticationProvider
    {
        return $this->plugin?->getMultiFactorAuthenticationProvider($this->providerId);
    }

    #[Computed]
    public function user(): ?Authenticatable
    {
        return $this->userProvider->retrieveById(
            Crypt::decrypt($this->userId)
        );
    }

    #[Computed]
    public function userProvider(): UserProvider
    {
        return Filament::auth()->getProvider() ?? auth()->guard('web')->getProvider();
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::auth/multi-factor/webauthn/actions/register-on-domain.title');
    }

    public function mount(Request $request): void
    {
        $this->userId = $request->query('user');
        $this->providerId = $request->query('providerId');
        $this->origin = $request->query('origin');

        if (! $this->providerInstance) {
            throw new LogicException('A multi-factor authentication provider instance was not resolved.');
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('register');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                return [
                    Text::make(__('profile-filament::auth/multi-factor/webauthn/actions/register-on-domain.form.messages.prompt'))
                        ->color('neutral')
                        ->size(Size::Medium)
                        ->extraAttributes([
                            'class' => 'text-center text-pretty',
                        ]),

                    Text::make(fn () => $this->getErrorBag()->first('securityKey'))
                        ->color('danger')
                        ->size(Size::Medium)
                        ->extraAttributes([
                            'class' => 'block text-center text-pretty',
                        ])
                        ->visible(fn () => $this->getErrorBag()->has('securityKey')),

                    View::make('profile-filament::partials.multi-factor.webauthn.cross-domain-registration'),

                    Actions::make([
                        Action::make('register')
                            ->label(__('profile-filament::auth/multi-factor/webauthn/actions/register-on-domain.form.actions.register.label'))
                            ->extraAttributes([
                                'class' => 'webauthn-register',
                            ])
                            ->action(function (HasActions $livewire) {
                                $rateLimitingKey = 'pf-set-up-webauthn:' . $this->user->getAuthIdentifier();

                                if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                                    $this->getRateLimitedNotification(
                                        new TooManyRequestsException(
                                            static::class,
                                            'register',
                                            request()->ip(),
                                            RateLimiter::availableIn($rateLimitingKey),
                                        )
                                    )?->send();

                                    return;
                                }

                                RateLimiter::hit($rateLimitingKey);

                                $livewire->dispatch('webauthnRegistrationReady', [
                                    'webauthnOptions' => json_decode($this->providerInstance->generateRegistrationOptions($this->user)),
                                ]);
                            }),
                    ])->fullWidth(),
                ];
            });
    }

    public function register(array $arguments): void
    {
        $securityKeyJson = data_get($arguments, 'securityKey');
        if (blank($securityKeyJson)) {
            $this->throwFailureValidationException();
        }

        // Generate a random name for the key for now. We'll use the user-submitted name
        // once the parent window receives the key. This is to prevent unique validation
        // failing on the key's name.
        $data = [
            'name' => 'key-' . now()->unix(),
        ];

        $securityKey = DB::transaction(function () use ($securityKeyJson, $data) {
            try {
                return $this->providerInstance->storeSecurityKey(
                    $securityKeyJson,
                    $data,
                    request()->getHost(),
                    $this->user,
                );
            } catch (Throwable) {
                $this->throwFailureValidationException();
            }
        });

        $userId = Js::from($this->userId);

        $securityKeyId = Js::from(
            Crypt::encrypt((string) $securityKey->getKey())
        );

        // Ensure only our origin window can listen for the message.
        $origin = Js::from(
            Uri::of("https://{$this->origin}")->value()
        );

        $this->js(<<<JS
        if (! window.opener) {
            return;
        }

        window.opener.postMessage({
            type: 'webauthn-external-success',
            userId: {$userId},
            securityKeyId: {$securityKeyId},
        }, {$origin});
        JS);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'securityKey' => __('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.failed'),
        ]);
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->danger()
            ->title(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(
                array_key_exists('body', __('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.throttled'))
                    ? __('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.throttled.body', [
                        'seconds' => $exception->secondsUntilAvailable,
                        'minutes' => $exception->minutesUntilAvailable,
                    ])
                    : null
            );
    }
}
