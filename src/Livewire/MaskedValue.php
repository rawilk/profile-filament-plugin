<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;

class MaskedValue extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use UsesSudoChallengeAction;

    #[Locked]
    public bool $reveal = false;

    #[Locked]
    public string $maskedValue = '';

    public Model $model;

    #[Locked]
    public string $field;

    #[Locked]
    public bool $requiresSudo = false;

    #[Locked]
    public bool $copyable = false;

    #[Locked]
    public ?string $copyMessage = null;

    #[Locked]
    public int $copyMessageDuration = 2000;

    public function revealAction(): Action
    {
        return Action::make('reveal')
            ->link()
            ->view('profile-filament::livewire.partials.masked-value-reveal', ['value' => $this->maskedValue])
            ->action(function () {
                $this->reveal = true;
            })
            ->mountUsing(function () {
                if ($this->requiresSudo) {
                    $this->ensureSudoIsActive(returnAction: 'reveal');
                }
            });
    }

    public function render(): View
    {
        return view('profile-filament::livewire.masked-value');
    }
}
