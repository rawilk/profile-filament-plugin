<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Writer;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\View;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Livewire\Component;
use PragmaRX\Google2FAQRCode\Google2FA;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Actions\SetUpAuthenticatorAppAction;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Contracts\AuthenticatorApps\ConfirmTwoFactorAppAction;
use Rawilk\ProfileFilament\Contracts\AuthenticatorApps\DeleteAuthenticatorAppAction as Deleter;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use SensitiveParameter;

class AppAuthenticationProvider implements MultiFactorAuthenticationProvider
{
    use Concerns\VerifiesOneTimeCodes;

    public const string ID = 'app';

    protected ?string $brandName = null;

    /**
     * The number of authenticator apps a user may register to their account.
     */
    protected int $appRegistrationLimit = 3;

    protected int $appSecretKeyLength = 32;

    public function __construct(
        protected Google2FA $google2FA,
    ) {
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function generateSecret(): string
    {
        return $this->google2FA->generateSecretKey(length: $this->appSecretKeyLength);
    }

    public function generateQrCodeDataUri(string $secret): string
    {
        /** @var HasAppAuthentication $user */
        $user = Filament::auth()->user();

        $inlineQrCode = $this->google2FA->getQRCodeInline(
            $this->getBrandName(),
            $this->getHolderName($user),
            $secret,
        );

        // This is a fallback for when `bacon/bacon-qr-code` is installed but the `imagick` extension is not.
        if (
            class_exists(Writer::class)
            && class_exists(ImageRenderer::class)
            && (! extension_loaded('imagick'))
        ) {
            $inlineQrCode = 'data:image/svg+xml;base64,' . base64_encode($inlineQrCode);
        }

        return $inlineQrCode;
    }

    public function getHolderName(HasAppAuthentication $user): string
    {
        return $user->getAppAuthenticationHolderName();
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function getSelectLabel(): string
    {
        return __('profile-filament::auth/multi-factor/app/provider.management-schema.select-label');
    }

    public function getManagementSchemaComponents(): array
    {
        $user = Filament::auth()->user();

        return [
            Flex::make([
                View::make('profile-filament::components.multi-factor.provider-title')
                    ->viewData(fn () => [
                        'icon' => ProfileFilamentIcon::MfaTotp->resolve(),
                        'label' => __('profile-filament::auth/multi-factor/app/provider.management-schema.label'),
                        'description' => __('profile-filament::auth/multi-factor/app/provider.management-schema.description'),
                        'configuredLabel' => __('profile-filament::auth/multi-factor/app/provider.management-schema.messages.configured'),
                        'isEnabled' => $this->isEnabled($user),
                    ]),

                Actions::make($this->getActions())->grow(false),
            ]),

            View::make('profile-filament::partials.multi-factor.app.authenticator-app-list')
                ->visible(fn (): bool => $user->authenticatorApps->isNotEmpty())
                ->viewData([
                    'authenticatorApps' => $user->authenticatorApps,
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
            SetUpAuthenticatorAppAction::make($this)
                ->label(fn (): string => $this->isEnabled($user) ? __('profile-filament::auth/multi-factor/app/actions/set-up.another-label') : __('profile-filament::auth/multi-factor/app/actions/set-up.label'))
                ->hidden(fn (): bool => $this->authenticatorAppLimitHasBeenReached($user))
                ->after(function (Component $livewire): void {
                    $livewire->js('$wire.$refresh');
                }),
        ];
    }

    public function saveApp(HasAppAuthentication $user, #[SensitiveParameter] ?string $secret, string $appName): void
    {
        app(ConfirmTwoFactorAppAction::class)(
            user: $user,
            name: $appName,
            secret: $secret,
        );
    }

    public function deleteApp(AuthenticatorApp $app): void
    {
        app(Deleter::class)($app);
    }

    public function appSecretKeyLength(int $length): static
    {
        $this->appSecretKeyLength = $length;

        return $this;
    }

    public function brandName(?string $brandName): static
    {
        $this->brandName = $brandName;

        return $this;
    }

    public function getBrandName(): string
    {
        return $this->brandName ?? strip_tags(Filament::getBrandName());
    }

    public function limitAppRegistrationsTo(int $limit): static
    {
        $this->appRegistrationLimit = $limit;

        return $this;
    }

    public function getAppRegistrationLimit(): int
    {
        return $this->appRegistrationLimit;
    }

    public function authenticatorAppLimitHasBeenReached(User $user): bool
    {
        $limit = $this->getAppRegistrationLimit();
        if ($limit < 1) {
            return true;
        }

        return $user->authenticatorApps->count() >= $limit;
    }

    /**
     * @param  User&HasAppAuthentication  $user
     */
    public function getChallengeFormComponents(User $user): array
    {
        return [
            OneTimeCodeInput::make('code')
                ->label(__('profile-filament::auth/multi-factor/app/provider.challenge-form.code.label'))
                ->validationAttribute(__('profile-filament::auth/multi-factor/app/provider.challenge-form.code.validation-attribute'))
                ->required()
                ->autofocus()
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, $value, Closure $fail) use ($user): void {
                        if ($this->isValidCodeForAnApp($value, $user)) {
                            return;
                        }

                        $fail(__('profile-filament::auth/multi-factor/app/provider.challenge-form.code.messages.invalid'));
                    };
                }),
        ];
    }

    public function getChallengeSubmitLabel(): ?string
    {
        return __('profile-filament::auth/multi-factor/app/provider.challenge-form.actions.authenticate.label');
    }

    public function getChangeToProviderActionLabel(Authenticatable $user): ?string
    {
        return __('profile-filament::auth/multi-factor/app/provider.challenge-form.actions.change-provider.label');
    }
}
