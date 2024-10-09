<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\RegisterPasskeyAction;
use Rawilk\ProfileFilament\Models\WebauthnKey;

/**
 * @property-read User $user
 * @property-read Collection<int, WebauthnKey> $passkeys
 * @property-read null|WebauthnKey $upgrading
 */
#[On(MfaEvent::PasskeyDeleted->value)]
class PasskeyManager extends ProfileComponent
{
    #[Locked]
    public int|null|string $idToUpgrade = null;

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    #[Computed]
    public function passkeys(): Collection
    {
        return $this->user->passkeys;
    }

    #[Computed]
    public function upgrading(): ?WebauthnKey
    {
        if (blank($this->idToUpgrade)) {
            return null;
        }

        return $this->user
            ->nonPasskeyWebauthnKeys()
            ->whereKey($this->idToUpgrade)
            ->first();
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <x-filament::section
                :header-actions="$this->passkeys->isEmpty() ? [] : [
                    $this->addAction
                ]"
            >
                <x-slot:heading>
                    <span id="{{ $this->getId() }}.heading">
                        {{ __('profile-filament::pages/security.passkeys.title') }}
                    </span>
                </x-slot:heading>

                @includeWhen($this->passkeys->isEmpty(), 'profile-filament::livewire.partials.no-passkeys')

                <div>
                    @if ($this->passkeys->isNotEmpty())
                        <div class="text-sm mb-4 text-pretty">
                            {{ str(__('profile-filament::pages/security.passkeys.list.description'))->markdown()->toHtmlString() }}
                        </div>

                        <div
                            id="{{ $this->getId() }}.passkey-list"
                            class="border rounded-md dark:border-gray-700 divide-y dark:divide-gray-700"
                        >
                            <ul
                                role="list"
                                class="divide-y dark:divide-gray-700"
                                aria-labelledby="{{ $this->getId() }}.heading"
                            >
                                @foreach ($this->passkeys as $passkey)
                                    <li
                                        class="px-4 py-3"
                                        wire:key="{{ $this->getId() }}.passkeys.{{ $passkey->getRouteKey() }}"
                                    >
                                        @livewire(\Rawilk\ProfileFilament\Livewire\Passkey::class, [
                                            'passkey' => $passkey,
                                        ], key('passkey.' . $passkey->getRouteKey()))
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    #[On(MfaEvent::WebauthnKeyDeleted->value)]
    #[On(MfaEvent::WebauthnKeyUpgradedToPasskey->value)]
    public function onKeyDeleted(): void
    {
        $this->idToUpgrade = null;

        unset($this->upgrading);
    }

    #[On(MfaEvent::PasskeyRegistered->value)]
    public function onKeyRegistered(): void
    {
        $this->unmountAction();

        $this->idToUpgrade = null;

        unset($this->passkeys, $this->upgrading);
    }

    #[On(MfaEvent::StartPasskeyUpgrade->value)]
    public function startUpgrade(int|string $id): void
    {
        $this->idToUpgrade = $id;

        $this->mountAction('add', ['excludeId' => $id]);
    }

    public function addAction(): Action
    {
        return RegisterPasskeyAction::make('add')
            ->upgrading(fn (): ?WebauthnKey => $this->upgrading)
            ->preCheck(function (array $arguments) {
                if (blank(data_get($arguments, 'excludeId'))) {
                    $this->idToUpgrade = null;

                    unset($this->upgrading);
                }
            });
    }
}
