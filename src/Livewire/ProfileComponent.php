<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read \Filament\Contracts\Plugin $profilePlugin
 */
abstract class ProfileComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    abstract protected function view(): string;

    #[Computed]
    public function profilePlugin(): \Filament\FilamentManager|\Filament\Contracts\Plugin
    {
        return filament(ProfileFilamentPLugin::PLUGIN_ID);
    }

    public function render(): View
    {
        return view($this->view());
    }
}
