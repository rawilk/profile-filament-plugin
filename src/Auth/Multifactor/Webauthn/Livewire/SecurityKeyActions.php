<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Livewire;

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
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Actions\DeleteSecurityKeyAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpdated;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\Webauthn\SecurityKeyNameInput;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read WebauthnProvider $webauthnProvider
 */
#[Lazy]
class SecurityKeyActions extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?WebauthnKey $record;

    #[Computed]
    public function webauthnProvider(): WebauthnProvider
    {
        /** @var WebauthnProvider $provider */
        $provider = filament(ProfileFilamentPlugin::PLUGIN_ID)->getMultiFactorAuthenticationProvider(WebauthnProvider::ID);

        return $provider;
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            @if (filled($record?->getKey()))
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
            ->label(fn () => __('profile-filament::auth/multi-factor/webauthn/actions/edit-name.label', ['name' => $this->record->name]))
            ->hiddenLabel()
            ->tooltip(__('profile-filament::auth/multi-factor/webauthn/actions/edit-name.tooltip'))
            ->modalHeading(__('profile-filament::auth/multi-factor/webauthn/actions/edit-name.modal.heading'))
            ->modalWidth(Width::Large)
            ->icon(FilamentIcon::resolve(ActionsIconAlias::EDIT_ACTION) ?? Heroicon::OutlinedPencil)
            ->size(Size::Small)
            ->outlined()
            ->modalSubmitActionLabel(__('profile-filament::auth/multi-factor/webauthn/actions/edit-name.modal.actions.submit.label'))
            ->record($this->record)
            ->schema([
                SecurityKeyNameInput::make('name'),
            ])
            ->authorize('update', $this->record)
            ->successNotificationTitle(__('profile-filament::auth/multi-factor/webauthn/actions/edit-name.notifications.updated.title'))
            ->after(function (WebauthnKey $record) {
                WebauthnKeyUpdated::dispatch($record, Filament::auth()->user());

                $this->js(<<<'JS'
                $wire.$parent.$refresh
                JS);
            });
    }

    protected function deleteAction(): Action
    {
        return DeleteSecurityKeyAction::make()
            ->record($this->record)
            ->provider(fn () => $this->webauthnProvider)
            ->after(function () {
                $this->record = null;

                $this->js(<<<'JS'
                $wire.$parent.$refresh
                JS);
            });
    }
}
