<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Component as FormComponent;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Support\Timebox;
use Livewire\Component;
use Rawilk\FilamentPasswordInput\Password;
use Rawilk\ProfileFilament\Concerns\Sudo\ChallengesSudoMode;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Facades\Webauthn;
use RuntimeException;
use Throwable;

class SudoChallengeAction extends Action
{
    use ChallengesSudoMode;

    protected ?User $user = null;

    protected ?string $error = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->sudoModeIsActive()) {
            // Require user to re-authenticate to perform the sensitive action.
            $this->requiresConfirmation()
                ->modalHeading(fn (): string => __('profile-filament::messages.sudo_challenge.title'))
                ->modalDescription(null)
                ->modalIcon(FilamentIcon::resolve('sudo::challenge') ?? 'heroicon-m-finger-print')
                ->modalIconColor('primary')
                ->modalContent(fn (Component $livewire) => view('profile-filament::livewire.sudo.modal-content', $this->getSudoViewData($livewire)))
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalWidth('lg')
                ->mountUsing(function (Component $livewire, Request $request) {
                    $this->ensureTraitIsUsedOnComponent($livewire);

                    $livewire->sudoChallengeData = [];
                    $livewire->sudoChallengeMode = null;
                    $livewire->hasSudoWebauthnError = false;

                    SudoModeChallenged::dispatch($this->user(), $request);
                })
                ->action(function (Component $livewire, Action $action, array $arguments, Request $request) {
                    $this->parseSudoArguments($action, $livewire, [...$arguments, ...$this->extractArgumentsFromRequest($request)]);

                    // If we made it here, we can activate sudo mode.
                    Sudo::activate();
                    SudoModeActivated::dispatch($this->user(), $request);

                    if (Arr::has($arguments, 'returnAction')) {
                        $livewire->replaceMountedAction($arguments['returnAction'], Arr::except($arguments, ['returnAction', 'method', 'assertion']));

                        $action->halt();
                    }
                });
        }
    }

    public function call(array $parameters = []): mixed
    {
        // If sudo mode is already enabled, just extend it.
        if ($this->sudoModeIsActive()) {
            Sudo::extend();
        }

        return parent::call($parameters);
    }

    protected function parseSudoArguments(Action $action, Component $livewire, array $arguments): void
    {
        if (Arr::has($arguments, 'mode')) {
            $livewire->sudoChallengeMode = $arguments['mode'];
            $livewire->sudoChallengeData = [];
            $livewire->hasSudoWebauthnError = false;

            $action->halt();
        }

        if (Arr::has($arguments, 'method') && $arguments['method'] === 'confirm') {
            App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($livewire, $action, $arguments) {
                $this->confirmIdentity(
                    data: $livewire->sudoChallengeMode === SudoChallengeMode::Webauthn->value
                        ? $arguments
                        : $livewire->sudoChallengeData,
                    livewire: $livewire,
                    action: $action,
                );

                $timebox->returnEarly();
            }, microseconds: 300 * 1000);

            return;
        }

        // If we've reached this point, something went wrong. Make sure sudo mode isn't accidentally activated.
        $action->halt();
    }

    protected function getSudoViewData(Component $livewire): array
    {
        $challengeOptions = $this->sudoChallengeOptionsFor($this->user());

        if (! $livewire->sudoChallengeMode) {
            $livewire->sudoChallengeMode = ProfileFilament::preferredSudoChallengeMethodFor($this->user(), $challengeOptions);
        }

        return [
            'alternateChallengeOptions' => $this->mapAlternateChallengeOptions($challengeOptions, $livewire->sudoChallengeMode, $this->user()),
            'user' => $this->user(),
            'sudoForm' => $this->getSudoForm($livewire),
            'sudoError' => $this->error,
        ];
    }

    protected function getSudoForm(Component $livewire): ?Form
    {
        if ($livewire->sudoChallengeModeEnum === SudoChallengeMode::Webauthn) {
            return null;
        }

        return Form::make($livewire)
            ->schema([
                $livewire->sudoChallengeModeEnum === SudoChallengeMode::Password
                    ? $this->getPasswordInput()
                    : $this->getTotpInput(),
            ]);
    }

    protected function getPasswordInput(): FormComponent
    {
        return Password::make('password')
            ->id('sudo_challenge.password')
            ->label(__('profile-filament::messages.sudo_challenge.password.input_label'))
            ->statePath('sudoChallengeData.password')
            ->hint(
                filament()->hasPasswordReset()
                    ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()">{{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>'))
                    : null,
            )
            ->required()
            ->extraInputAttributes([
                'x-on:keydown.enter.prevent.stop' => '$wire.callMountedAction(' . Js::from(['method' => 'confirm']) . ')',
            ]);
    }

    protected function getTotpInput(): FormComponent
    {
        return TextInput::make('code')
            ->id('sudo_challenge.totp')
            ->hiddenLabel()
            ->placeholder(__('profile-filament::messages.sudo_challenge.totp.placeholder'))
            ->helperText(__('profile-filament::messages.sudo_challenge.totp.help_text'))
            ->statePath('sudoChallengeData.totp')
            ->required()
            ->extraInputAttributes([
                'x-on:keydown.enter.prevent.stop' => '$wire.callMountedAction(' . Js::from(['method' => 'confirm']) . ')',
            ]);
    }

    protected function user(): User
    {
        return $this->user ?? ($this->user = filament()->auth()->user());
    }

    protected function confirmIdentity(array $data, Component $livewire, Action $action): void
    {
        switch ($livewire->sudoChallengeMode) {
            case SudoChallengeMode::App->value:
                if (! Mfa::usingChallengedUser($this->user())->isValidTotpCode($data['totp'] ?? '')) {
                    $this->error = __('profile-filament::messages.sudo_challenge.totp.invalid');

                    $action->halt();
                }

                break;

            case SudoChallengeMode::Webauthn->value:
                try {
                    Webauthn::verifyAssertion(
                        user: $this->user(),
                        assertionResponse: $data['assertion'],
                        storedPublicKey: unserialize(session()->pull(SudoSession::WebauthnAssertionPk->value)),
                    );
                } catch (Throwable) {
                    $this->error = __('profile-filament::messages.sudo_challenge.webauthn.invalid');
                    $livewire->hasSudoWebauthnError = true;

                    $action->halt();
                }

                break;

            case SudoChallengeMode::Password->value:
                if (! Hash::check($data['password'] ?? '', $this->user()->getAuthPassword())) {
                    $this->error = __('profile-filament::messages.sudo_challenge.password.invalid');

                    $action->halt();
                }

                break;

            default:
                throw new Exception('Sudo challenge mode "' . $livewire->sudoChallengeMode . '" is not supported by this package.');
        }
    }

    protected function ensureTraitIsUsedOnComponent(Component $livewire): void
    {
        throw_unless(
            in_array(UsesSudoChallengeAction::class, class_uses_recursive($livewire), true),
            new RuntimeException('The trait "' . UsesSudoChallengeAction::class . '" must be used on your livewire component to use this action.'),
        );
    }

    /**
     * Something changed in 3.1.0 of filament that is breaking our sudo challenge action.
     * Since Livewire sends the payloads of the requests, we should be able to rely
     * on fetching our parameters we're sending the action for now.
     */
    protected function extractArgumentsFromRequest(Request $request): array
    {
        return Arr::get($request->all(), 'components.0.calls.0.params.0', []);
    }
}
