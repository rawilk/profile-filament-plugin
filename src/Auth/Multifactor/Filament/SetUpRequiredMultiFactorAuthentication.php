<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Filament;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read ProfileFilamentPlugin $plugin
 * @property-read Authenticatable $user
 */
class SetUpRequiredMultiFactorAuthentication extends SimplePage
{
    #[Computed]
    public function plugin(): ProfileFilamentPlugin
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID);
    }

    #[Computed]
    public function user(): Authenticatable
    {
        return Filament::auth()->user();
    }

    public function mount(): void
    {
        if ((! $this->plugin->hasMultiFactorAuthentication()) || $this->isEnabled()) {
            redirect()->intended(Filament::getUrl());
        }
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FiveExtraLarge;
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::auth/multi-factor/pages/set-up-required-multi-factor-authentication.title');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('profile-filament::auth/multi-factor/pages/set-up-required-multi-factor-authentication.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('profile-filament::auth/multi-factor/pages/set-up-required-multi-factor-authentication.subheading');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(
                    new HtmlString(Blade::render('<x-profile-filament::plugin-css />'))
                ),

                $this->getMultiFactorAuthenticationContentComponents(),
                $this->getFooterActionsComponent(),
            ]);
    }

    public function getMultiFactorAuthenticationContentComponents(): Component
    {
        $user = $this->user;

        return Section::make()
            ->divided()
            ->schema([
                ...collect($this->plugin->getMultiFactorAuthenticationProviders())
                    ->map(
                        fn (MultiFactorAuthenticationProvider $provider): Component => Group::make($provider->getManagementSchemaComponents())
                            ->statePath($provider->getId())
                    )
                    ->all(),

                ...$this->getRecoveryComponents(),
            ]);
    }

    public function getFooterActionsComponent(): Component
    {
        return Actions::make($this->getfooterActions())
            ->fullWidth();
    }

    /**
     * @return array<Action>
     */
    public function getFooterActions(): array
    {
        return [
            $this->getContinueAction(),
        ];
    }

    public function getContinueAction(): Action
    {
        return Action::make('continue')
            ->label(__('profile-filament::auth/multi-factor/pages/set-up-required-multi-factor-authentication.actions.continue.label'))
            ->action(fn () => redirect()->intended(Filament::getUrl()))
            ->visible($this->isEnabled(...));
    }

    public function isEnabled(): bool
    {
        $user = $this->user;

        if (! ($user instanceof HasMultiFactorAuthentication)) {
            throw new LogicException('User model must be implement the [' . HasMultiFactorAuthentication::class . '] interface to use the requires multi factor authentication middleware.');
        }

        return $user->hasMultiFactorAuthenticationEnabled();
    }

    protected function getRecoveryComponents(): array
    {
        if (! $this->plugin->isMultiFactorRecoverable()) {
            return [];
        }

        return [
            Group::make($this->plugin->getMultiFactorRecoveryProvider()->getManagementSchemaComponents())
                ->statePath('recovery'),
        ];
    }
}
