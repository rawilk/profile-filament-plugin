<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Sudo;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Http\Request;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Facades\Sudo;

class SudoChallengeForm extends Component implements HasActions, HasForms
{
    use Concerns\HasSudoChallengeForm;
    use InteractsWithActions;
    use InteractsWithForms;

    #[Locked]
    public ?string $sudoCaller = null;

    #[Locked]
    public ?string $sudoCallerMethod = null;

    #[Locked]
    public ?array $sudoCallerData = null;

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <x-filament-actions::modals />
        </div>
        HTML;
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema(fn () => match ($this->challengeMode) {
                default => [],
                SudoChallengeMode::Password => $this->passwordSchema(),
                SudoChallengeMode::App => $this->authenticatorAppSchema(),
            });
    }

    #[On('check-sudo')]
    public function checkSudo(string $caller = '', ?string $method = null, array $data = []): void
    {
        if (Sudo::isActive()) {
            Sudo::extend();

            $this->dispatch('sudo-active', method: $method, data: $data)->to($caller);

            return;
        }

        $this->sudoCaller = $caller;
        $this->sudoCallerMethod = $method;
        $this->sudoCallerData = $data;

        $this->mountAction('sudoChallenge');
    }

    public function sudoChallengeAction(): Action
    {
        return Action::make('sudoChallenge')
            ->requiresConfirmation()
            ->modalHeading(__('profile-filament::messages.sudo_challenge.title'))
            ->modalDescription(null)
            ->modalIcon(FilamentIcon::resolve('sudo::challenge') ?? 'heroicon-m-finger-print')
            ->modalIconColor('primary')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth(MaxWidth::Large)
            ->modalContent(
                fn () => view('profile-filament::components.sudo.form', [
                    'user' => $this->user,
                    'userHandle' => $this->userHandle(),
                    'challengeMode' => $this->challengeMode,
                    'alternateChallengeOptions' => $this->alternateChallengeOptions,
                    'error' => $this->error,
                    'form' => $this->form,
                ])
            )
            ->mountUsing(function (Request $request) {
                SudoModeChallenged::dispatch($this->user, $request);

                $this->form->fill();

                unset($this->challengeOptions, $this->alternateChallengeOptions, $this->challengeMode);

                $this->error = null;
                $this->mode = ProfileFilament::preferredSudoChallengeMethodFor($this->user, $this->challengeOptions);
                $this->hasWebauthnError = false;
            });
    }

    protected function onConfirmed(): void
    {
        $this->dispatch('sudo-active', method: $this->sudoCallerMethod, data: $this->sudoCallerData)->to($this->sudoCaller);

        $this->unmountAction(shouldCancelParentActions: false);
    }
}
