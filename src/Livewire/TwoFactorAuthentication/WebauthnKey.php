<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\DeleteWebauthnKeyAction;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\EditWebauthnKeyAction;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Models\WebauthnKey as WebauthnKeyModel;

/**
 * @property-read bool $hasPasskeys
 * @property-read User $user
 * @property-read null|WebauthnKeyModel $webauthnKey
 */
class WebauthnKey extends ProfileComponent
{
    #[Locked]
    public int|string|null $id = null;

    #[Computed]
    public function hasPasskeys(): bool
    {
        return $this->profilePlugin->panelFeatures()->hasPasskeys();
    }

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    #[Computed]
    public function webauthnKey(): ?WebauthnKeyModel
    {
        if (blank($this->id)) {
            return null;
        }

        return $this->user
            ->nonPasskeyWebauthnKeys()
            ->whereKey($this->id)
            ->first([
                'id',
                'user_id',
                'name',
                'attachment_type',
                'is_passkey',
                'last_used_at',
                'created_at',
            ]);
    }

    public function mount(): void
    {
        if (! $this->webauthnKey) {
            $this->id = null;
        }
    }

    public function render(): string
    {
        return <<<'HTML'
        <div @class(['hidden' => ! $this->id])>
            @if ($this->id)
                <div class="py-3 flex justify-between items-center gap-x-3">
                    <div>
                        <div>
                            <span>{{ $this->webauthnKey->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">
                                {{ $this->webauthnKey->registered_at }}
                            </span>
                        </div>

                        <div>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">
                                {{ $this->webauthnKey->last_used }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-x-2">
                        @if ($this->hasPasskeys && Gate::allows('upgradeToPasskey', $this->webauthnKey))
                            {{ $this->upgradeAction }}
                        @endif

                        @can('update', $this->webauthnKey)
                            {{ $this->editAction }}
                        @endcan

                        {{ $this->deleteAction }}
                    </div>
                </div>

                <x-filament-actions::modals />
            @endif
        </div>
        HTML;
    }

    #[On(MfaEvent::WebauthnKeyUpgradedToPasskey->value)]
    public function onUpgraded(int|string|null $upgradedFrom = null): void
    {
        if ($upgradedFrom === $this->id) {
            $this->id = null;

            unset($this->webauthnKey);
        }
    }

    public function editAction(): EditAction
    {
        return EditWebauthnKeyAction::make()
            ->record($this->webauthnKey);
    }

    public function deleteAction(): Action
    {
        return DeleteWebauthnKeyAction::make()
            ->record($this->webauthnKey)
            ->after(function (): void {
                $this->dispatch(MfaEvent::WebauthnKeyDeleted->value, id: $this->webauthnKey->getKey());

                $this->id = null;

                unset($this->webauthnKey);
            });
    }

    public function upgradeAction(): Action
    {
        return Action::make('upgrade')
            ->label(__('profile-filament::pages/security.passkeys.actions.upgrade.trigger_label', ['name' => e($this->webauthnKey->name)]))
            ->icon(fn () => FilamentIcon::resolve('mfa::upgrade-to-passkey') ?? 'heroicon-m-arrow-up')
            ->button()
            ->hiddenLabel()
            ->color('success')
            ->size(ActionSize::Small)
            ->outlined()
            ->tooltip(__('profile-filament::pages/security.passkeys.actions.upgrade.trigger_tooltip'))
            ->visible(
                fn (): bool => $this->hasPasskeys && Gate::allows('upgradeToPasskey', $this->webauthnKey)
            )
            ->extraAttributes([
                'title' => '',
            ])
            ->alpineClickHandler(
                Blade::render(<<<'JS'
                $dispatch(@js($event), @js(['id' => $id]));
                JS, [
                    'event' => MfaEvent::StartPasskeyUpgrade->value,
                    'id' => $this->webauthnKey->getKey(),
                ])
            );
    }
}
