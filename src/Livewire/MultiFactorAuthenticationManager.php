<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;

/**
 * @property-read Authenticatable&Model $user
 */
class MultiFactorAuthenticationManager extends ProfileComponent
{
    #[Computed]
    public function user(): Authenticatable&Model
    {
        $user = Filament::auth()->user();

        throw_unless(
            $user instanceof Model,
            new LogicException('The authenticated user object must be an Eloquent model to allow the multi factor authentication manager to update it.'),
        );

        return $user;
    }

    public function mount(): void
    {
        throw_unless(
            $this->user instanceof HasMultiFactorAuthentication,
            new LogicException('The authenticated user must implement the HasMultiFactorAuthentication interface to use the multi factor authentication manager.'),
        );

        $this->loadUserRelations();
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->content }}

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('profile-filament::pages/security.mfa.title'))
                    ->divided()
                    ->afterHeader(
                        fn (): Text => $this->hasMultiFactorAuthenticationEnabled()
                            ? Text::make(__('profile-filament::pages/security.mfa.messages.enabled'))
                                ->badge()
                                ->color('success')
                            : Text::make(__('profile-filament::pages/security.mfa.messages.disabled'))
                                ->badge()
                                ->color('danger')
                    )
                    ->schema([
                        ...collect($this->profilePlugin->getMultiFactorAuthenticationProviders())
                            ->map(
                                fn (MultiFactorAuthenticationProvider $provider): Component => Group::make($provider->getManagementSchemaComponents())
                                    ->statePath($provider->getId())
                            )
                            ->all(),

                        // Recovery
                        ...$this->getRecoveryComponents(),
                    ]),
            ]);
    }

    protected function hasMultiFactorAuthenticationEnabled(): bool
    {
        return $this->user->hasMultiFactorAuthenticationEnabled();
    }

    protected function getRecoveryComponents(): array
    {
        if (! $this->profilePlugin->isMultiFactorRecoverable()) {
            return [];
        }

        return [
            Group::make($this->profilePlugin->getMultiFactorRecoveryProvider()->getManagementSchemaComponents())
                ->statePath('recovery'),
        ];
    }

    protected function loadUserRelations(): void
    {
        $relations = [];

        if ($this->user instanceof HasAppAuthentication) {
            $relations['authenticatorApps'] = function (HasMany $query) {
                $query->select($query->qualifyColumns([
                    'id',
                    'user_id',
                    'name',
                    'created_at',
                    'last_used_at',
                    'secret',
                ]));
            };
        }

        $this->user->loadMissing($relations);
    }
}
