<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Contracts\Plugin;
use Filament\FilamentManager;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read ProfileFilamentPlugin $profilePlugin
 */
abstract class ProfileComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    #[Computed]
    public function profilePlugin(): FilamentManager|Plugin|ProfileFilamentPlugin
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID);
    }

    public function render(): View|string
    {
        if (method_exists($this, 'view')) {
            return view($this->view());
        }

        return '';
    }
}
