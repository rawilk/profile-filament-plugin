<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Forms\Components\TextInput\Actions\CopyAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use RateLimiter;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Actions\ShowRecoveryCodesAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\SudoChallengeAction;
use Rawilk\ProfileFilament\Auth\Sudo\Concerns\InteractsStaticlyWithSudo;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\AuthenticatorApps\AuthenticatorAppNameInput;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class SetUpAuthenticatorAppAction
{
    use InteractsStaticlyWithSudo;

    public static function make(AppAuthenticationProvider $provider): Action
    {
        return Action::make('setUpAuthenticatorApp')
            ->color('primary')
            ->size(Size::Small)
            ->modalWidth(Width::TwoExtraLarge)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->rateLimit(5)
            ->label(__('profile-filament::auth/multi-factor/app/actions/set-up.label'))
            ->before(function (HasActions $livewire, Request $request): void {
                if (! static::shouldChallengeForSudo()) {
                    return;
                }

                SudoModeChallenged::dispatch(Filament::auth()->user(), $request);

                $livewire->mountAction('sudoChallenge');
            })
            ->mountUsing(function (HasActions $livewire, Schema $form, Request $request) use ($provider): void {
                $livewire->mergeMountedActionArguments([
                    'encrypted' => Crypt::encrypt([
                        'secret' => $provider->generateSecret(),
                        'userId' => Filament::auth()->id(),
                    ]),
                ]);

                $form->fill([
                    'name' => __('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.name.default-name'),
                ]);

                if (! static::shouldChallengeForSudo()) {
                    static::extendSudo();

                    return;
                }

                SudoModeChallenged::dispatch(Filament::auth()->user(), $request);

                $livewire->mountAction('sudoChallenge');
            })
            ->modalHeading(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.heading'))
            ->modalDescription(new HtmlString(Blade::render(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.description'))))
            ->modalIcon(FilamentIcon::resolve(ProfileFilamentIcon::MfaTotp->value) ?? Heroicon::OutlinedDevicePhoneMobile)
            ->modalIconColor('primary')
            ->modalSubmitActionLabel(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.actions.submit.label'))
            ->registerModalActions([
                static::getCopyAction(),
                SudoChallengeAction::make(),
                ShowRecoveryCodesAction::make(actionName: 'showRecoveryCodes')
                    ->modalHeading(__('profile-filament::auth/multi-factor/recovery/actions/show-recovery-codes.modal.heading')),
            ])
            ->steps(fn (Action $action): array => [
                Step::make(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.steps.app.label'))
                    ->schema([
                        Group::make([
                            Text::make(new HtmlString(Blade::render(<<<'HTML'
                            <p class="font-bold mb-1">{{ __('profile-filament::auth/multi-factor/app/actions/set-up.modal.content.qr-code.title') }}</p>
                            <p>
                                {{ __('profile-filament::auth/multi-factor/app/actions/set-up.modal.content.qr-code.instruction') }}
                            </p>
                            HTML)))
                                ->color('neutral'),

                            Text::make(function () use ($provider, $action): Htmlable {
                                $secret = Crypt::decrypt($action->getArguments()['encrypted'])['secret'];

                                return new HtmlString(Blade::render(<<<'HTML'
                                <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded-md">
                                    <div class="fi-grid-col flex space-x-4" style="--col-span-default: span 1 / span 1">
                                        <div class="fi-sc-component shrink-0">
                                            <img
                                                alt="{{ __('profile-filament::auth/multi-factor/app/actions/set-up.modal.content.qr-code.alt') }}"
                                                src="{{ $image }}"
                                                class="fi-sc-image fi-align-left"
                                            >
                                        </div>

                                        <div class="pt-6 pr-2 flex-1">
                                            <div class="font-semibold mb-1">{{ __('profile-filament::auth/multi-factor/app/actions/set-up.modal.content.text-code.title') }}</div>
                                            <div class="text-gray-500 dark:text-gray-200">
                                                {{ __('profile-filament::auth/multi-factor/app/actions/set-up.modal.content.text-code.instruction') }}
                                            </div>

                                            <div class="bg-gray-200 dark:bg-gray-600 p-2 rounded-md font-mono mt-2.5 mb-2">
                                                {{ $secret }}
                                            </div>

                                            {{ $action->getModalAction('copySecret') }}
                                        </div>
                                    </div>
                                </div>
                                HTML, [
                                    'image' => $provider->generateQrCodeDataUri($secret),
                                    'secret' => $secret,
                                    'action' => $action,
                                ]));
                            })
                                ->extraAttributes([
                                    'class' => 'w-full',
                                ])
                                ->color('neutral'),
                        ])
                            ->dense(),

                        Text::make(new HtmlString(Blade::render(<<<'HTML'
                            <p class="font-bold mb-1">{{ __('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.code.title') }}</p>
                            <p>
                                {{ __('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.code.instruction') }}
                            </p>
                            HTML)))
                            ->color('neutral'),

                        OneTimeCodeInput::make('code')
                            ->label(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.code.label'))
                            ->validationAttribute(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.code.validation-attribute'))
                            ->belowContent(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.code.below-content'))
                            ->required()
                            ->rule(function () use ($action, $provider): Closure {
                                return function (string $attribute, $value, Closure $fail) use ($action, $provider): void {
                                    $rateLimitingKey = 'pf-set-up-app-authentication:' . Filament::auth()->user()->getAuthIdentifier();

                                    if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                                        $fail(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.code.messages.throttled'));

                                        return;
                                    }

                                    RateLimiter::hit($rateLimitingKey);

                                    if ($provider->verifyCode($value, Crypt::decrypt($action->getArguments()['encrypted'])['secret'])) {
                                        return;
                                    }

                                    $fail(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.code.messages.invalid'));
                                };
                            }),
                    ]),

                Step::make(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.form.steps.name.label'))
                    ->schema([
                        AuthenticatorAppNameInput::make('name'),
                    ]),
            ])
            ->action(function (array $arguments, array $data, Action $action, HasActions $livewire) use ($provider): void {
                if (static::shouldChallengeForSudo()) {
                    $action->cancel();
                }

                /** @var \Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication|\Illuminate\Contracts\Auth\Authenticatable $user */
                $user = Filament::auth()->user();

                $encrypted = Crypt::decrypt($arguments['encrypted']);

                if ($user->getAuthIdentifier() !== $encrypted['userId']) {
                    // Avoid encrypted arguments being passed between users by verifying that the authenticated
                    // user is the same as the user that the encrypted arguments were issued for.
                    return;
                }

                DB::transaction(function () use ($provider, $user, $encrypted, $data, $livewire): void {
                    $provider->saveApp(
                        $user,
                        $encrypted['secret'],
                        $data['name'],
                    );

                    /** @var ProfileFilamentPlugin $plugin */
                    $plugin = filament(ProfileFilamentPlugin::PLUGIN_ID);

                    if ($plugin->isMultiFactorRecoverable() && $user instanceof HasMultiFactorAuthenticationRecovery) {
                        $provider = $plugin->getMultiFactorRecoveryProvider();

                        if (! $provider->needsToBeSetup($user)) {
                            return;
                        }

                        $recoveryCodes = $provider->generateRecoveryCodes();
                        $provider->saveRecoveryCodes($user, $recoveryCodes);

                        $livewire->mountAction('showRecoveryCodes', arguments: [
                            'encrypted' => Crypt::encrypt([
                                'recoveryCodes' => $recoveryCodes,
                            ]),
                        ]);
                    }
                });

                Notification::make()
                    ->success()
                    ->title(__('profile-filament::auth/multi-factor/app/actions/set-up.notifications.enabled.title'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->send();
            });
    }

    protected static function getCopyAction(): CopyAction
    {
        return CopyAction::make('copySecret')
            ->size(Size::Small)
            ->label(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.content.text-code.actions.copy.label'))
            ->copyMessage(__('profile-filament::auth/multi-factor/app/actions/set-up.modal.content.text-code.actions.copy.copied'))
            ->alpineClickHandler(function (CopyAction $action): string {
                $secret = Crypt::decrypt($action->getParentAction()->getArguments()['encrypted'])['secret'];
                $copyMessageJs = Js::from($action->getCopyMessage($secret));
                $copyMessageDurationJs = Js::from($action->getCopyMessageDuration($secret));
                $copyableState = Js::from($secret);

                return <<<JS
                window.navigator.clipboard.writeText({$copyableState});
                \$tooltip({$copyMessageJs}, {
                    theme: \$store.theme,
                    timeout: {$copyMessageDurationJs},
                });
                JS;
            });
    }
}
