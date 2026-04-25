<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Actions\View\ActionsIconAlias;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Filament\Actions\DeleteAuthenticatorAppAction;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUpdated;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\AuthenticatorApps\AuthenticatorAppNameInput;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read AppAuthenticationProvider $appAuthenticationProvider
 */
#[Lazy]
class AuthenticatorAppActions extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?AuthenticatorApp $authenticatorApp;

    #[Computed]
    public function appAuthenticationProvider(): AppAuthenticationProvider
    {
        /** @var AppAuthenticationProvider $provider */
        $provider = filament(ProfileFilamentPlugin::PLUGIN_ID)->getMultiFactorAuthenticationProvider(AppAuthenticationProvider::ID);

        return $provider;
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            @if (filled($authenticatorApp?->getKey()))
                {{ $this->content }}

                <x-filament-actions::modals />
            @endif
        </div>
        HTML;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Actions::make([
                    $this->editAction(),
                    $this->deleteAction(),
                ]),
            ]);
    }

    protected function editAction(): Action
    {
        return EditAction::make()
            ->label(fn () => __('profile-filament::auth/multi-factor/app/actions/edit-name.label', ['name' => $this->authenticatorApp->name]))
            ->hiddenLabel()
            ->tooltip(__('profile-filament::auth/multi-factor/app/actions/edit-name.tooltip'))
            ->modalHeading(__('profile-filament::auth/multi-factor/app/actions/edit-name.modal.heading'))
            ->modalWidth(Width::Large)
            ->icon(FilamentIcon::resolve(ActionsIconAlias::EDIT_ACTION) ?? Heroicon::OutlinedPencil)
            ->size(Size::Small)
            ->outlined()
            ->modalSubmitActionLabel(__('profile-filament::auth/multi-factor/app/actions/edit-name.modal.actions.submit.label'))
            ->record($this->authenticatorApp)
            ->schema([
                AuthenticatorAppNameInput::make('name'),
            ])
            ->authorize('update', $this->authenticatorApp)
            ->successNotificationTitle(__('profile-filament::auth/multi-factor/app/actions/edit-name.notifications.updated.title'))
            ->after(function (AuthenticatorApp $record) {
                TwoFactorAppUpdated::dispatch($record, Filament::auth()->user());

                $this->js(<<<'JS'
                $wire.$parent.$refresh
                JS);
            });
    }

    protected function deleteAction(): Action
    {
        return DeleteAuthenticatorAppAction::make()
            ->record($this->authenticatorApp)
            ->provider(fn () => $this->appAuthenticationProvider)
            ->after(function () {
                $this->authenticatorApp = null;

                $this->js('$wire.$parent.$refresh');
            });
    }
}
