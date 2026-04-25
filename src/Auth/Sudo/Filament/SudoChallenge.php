<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Filament;

use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Auth\Sudo\Concerns\IssuesSudoChallenge;
use Rawilk\ProfileFilament\Auth\Sudo\Facades\Sudo;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Facades\ProfileFilament;

class SudoChallenge extends SimplePage
{
    use IssuesSudoChallenge;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected string $view = 'profile-filament::pages.sudo-challenge';

    public function mount(): void
    {
        if ($this->sudoModeIsActive()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->currentProvider = ProfileFilament::preferredSudoChallengeProviderFor(
            $this->user,
            $this->enabledSudoProviders,
        );

        if ($this->currentProviderInstance instanceof HasBeforeChallengeHook) {
            $this->currentProviderInstance->beforeChallenge($this->user);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::auth/sudo/sudo.challenge.title');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('profile-filament::auth/sudo/sudo.challenge.heading');
    }

    public function authenticate(Request $request): void
    {
        if ($this->isSudoRateLimited($this->user)) {
            return;
        }

        $this->form->validate();

        Sudo::activate();
        SudoModeActivated::dispatch($this->user, $request);

        redirect()->intended(Filament::getUrl());
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
            ->livewireSubmitHandler('authenticate');
    }

    protected function sudoModeIsActive(): bool
    {
        return Sudo::isActive();
    }
}
