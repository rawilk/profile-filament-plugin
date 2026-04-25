<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Schemas\Components\Component as FilamentComponent;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Http\Request;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Sudo\Concerns\IssuesSudoChallenge;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Facades\Sudo;

class SudoChallengeActionForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use IssuesSudoChallenge;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->currentProvider = ProfileFilament::preferredSudoChallengeProviderFor(
            $this->user,
            $this->enabledSudoProviders,
        );

        if ($this->currentProviderInstance instanceof HasBeforeChallengeHook) {
            $this->currentProviderInstance->beforeChallenge($this->user);
        }
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <x-profile-filament::sudo.form-content
                class="pf-sudo-modal-form"
                :heading="$this->currentProviderInstance?->heading($this->user)"
                :icon="$this->currentProviderInstance?->icon()"
                :current-provider="$currentProvider"
            >
                {{ $this->content }}

                @unless (empty($this->alternateOptions->getComponents()))
                    <x-slot:alternatives>
                        {{ $this->alternateOptions }}
                    </x-slot:alternatives>
                @endunless
            </x-profile-filament::sudo.form-content>
        </div>
        HTML;
    }

    public function authenticate(Request $request): void
    {
        if ($this->isSudoRateLimited($this->user)) {
            return;
        }

        $this->form->validate();

        Sudo::activate();
        SudoModeActivated::dispatch($this->user, $request);

        $this->dispatch('sudo-confirmed');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): FilamentComponent
    {
        return Group::make([EmbeddedSchema::make('form')]);
    }
}
