<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Sudo;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Exceptions\Halt;
use Illuminate\Http\Request;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Facades\Sudo;

class SudoChallengeActionForm extends Component implements HasActions, HasForms
{
    use Concerns\HasSudoChallengeForm;
    use InteractsWithActions;
    use InteractsWithForms;

    #[Locked]
    public string $actionType = 'action';

    public function mount(): void
    {
        $this->mode = ProfileFilament::preferredSudoChallengeMethodFor($this->user, $this->challengeOptions);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <x-profile-filament::sudo.form
                :user="$this->user"
                :user-handle="$this->userHandle()"
                :challenge-mode="$this->challengeMode"
                :alternate-challenge-options="$this->alternateChallengeOptions"
                :error="$this->error"
                :form="$this->form"
            />
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

    public function confirm(Request $request, ?array $assertion = null): void
    {
        try {
            $this->confirmIdentity($assertion);
        } catch (Halt) {
            return;
        }

        Sudo::activate();
        SudoModeActivated::dispatch($this->user, $request);

        $this->dispatch('sudo-active');
    }
}
