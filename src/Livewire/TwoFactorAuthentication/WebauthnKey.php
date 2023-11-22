<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\Webauthn\DeleteWebauthnKeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpdated;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Models\WebauthnKey as WebauthnKeyModel;

/**
 * @property-read bool $hasPasskeys
 */
class WebauthnKey extends ProfileComponent
{
    use UsesSudoChallengeAction;

    public ?WebauthnKeyModel $webauthnKey;

    #[Computed]
    public function hasPasskeys(): bool
    {
        return $this->profilePlugin->panelFeatures()->hasPasskeys();
    }

    public function messages(): array
    {
        return [
            'mountedActionsData.0.name.unique' => __('profile-filament::pages/security.passkeys.unique_validation_error'),
        ];
    }

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->label(__('profile-filament::pages/security.mfa.webauthn.actions.edit.trigger_label', ['name' => e($this->webauthnKey->name)]))
            ->record($this->webauthnKey)
            ->icon(FilamentIcon::resolve('actions::edit-action') ?? 'heroicon-o-pencil')
            ->button()
            ->hiddenLabel()
            ->color('primary')
            ->size('sm')
            ->outlined()
            ->tooltip(__('profile-filament::pages/security.mfa.webauthn.actions.edit.trigger_tooltip'))
            ->before(function (EditAction $action, WebauthnKeyModel $record) {
                $this->authorize('edit', $record);
            })
            ->after(function (WebauthnKeyModel $record) {
                WebauthnKeyUpdated::dispatch($record, filament()->auth()->user());
            })
            ->form([
                TextInput::make('name')
                    ->label(__('profile-filament::pages/security.mfa.webauthn.actions.edit.name'))
                    ->placeholder(__('profile-filament::pages/security.mfa.webauthn.actions.edit.name_placeholder'))
                    ->autofocus()
                    ->required()
                    ->unique(
                        table: config('profile-filament.table_names.webauthn_key'),
                        ignorable: $this->webauthnKey,
                        modifyRuleUsing: function (Unique $rule) {
                            $rule->where('user_id', filament()->auth()->id());
                        },
                    )
                    ->maxlength(255)
                    ->autocomplete('off'),
            ])
            ->modalHeading(__('profile-filament::pages/security.mfa.webauthn.actions.edit.title'))
            ->successNotificationTitle(__('profile-filament::pages/security.mfa.webauthn.actions.edit.success_message'))
            ->extraAttributes([
                'title' => '',
            ]);
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('profile-filament::pages/security.mfa.webauthn.actions.delete.trigger_label', ['name' => e($this->webauthnKey->name)]))
            ->icon(FilamentIcon::resolve('actions::delete-action') ?? 'heroicon-o-trash')
            ->button()
            ->hiddenLabel()
            ->tooltip(__('profile-filament::pages/security.mfa.webauthn.actions.delete.trigger_tooltip'))
            ->color('danger')
            ->size('sm')
            ->outlined()
            ->action(function () {
                $this->ensureSudoIsActive(returnAction: 'delete');

                $this->authorize('delete', $this->webauthnKey);

                app(DeleteWebauthnKeyAction::class)($this->webauthnKey);

                Notification::make()
                    ->title(__('profile-filament::pages/security.mfa.webauthn.actions.delete.success_message', ['name' => e($this->webauthnKey->name)]))
                    ->success()
                    ->send();

                $this->dispatch(MfaEvent::WebauthnKeyDeleted->value, id: $this->webauthnKey->getKey());

                $this->webauthnKey = null;
            })
            ->requiresConfirmation()
            ->modalIcon(fn () => FilamentIcon::resolve('actions::delete-action.modal') ?? 'heroicon-o-trash')
            ->modalHeading(__('profile-filament::pages/security.mfa.webauthn.actions.delete.title'))
            ->modalDescription(
                new HtmlString(
                    Str::inlineMarkdown(__('profile-filament::pages/security.mfa.webauthn.actions.delete.description', ['name' => e($this->webauthnKey->name)]))
                )
            )
            ->modalSubmitActionLabel(__('profile-filament::pages/security.mfa.webauthn.actions.delete.confirm'))
            ->extraAttributes([
                'title' => '',
            ])
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'delete');
            });
    }

    public function upgradeAction(): Action
    {
        $eventName = MfaEvent::StartPasskeyUpgrade->value;
        $keyId = Js::from($this->webauthnKey->getKey());

        return Action::make('upgrade')
            ->livewireClickHandlerEnabled(false)
            ->label(__('profile-filament::pages/security.passkeys.actions.upgrade.trigger_label', ['name' => e($this->webauthnKey->name)]))
            ->icon(fn () => FilamentIcon::resolve('mfa::upgrade-to-passkey') ?? 'heroicon-m-arrow-up')
            ->button()
            ->hiddenLabel()
            ->color('success')
            ->size('sm')
            ->outlined()
            ->tooltip(__('profile-filament::pages/security.passkeys.actions.upgrade.trigger_tooltip'))
            ->visible(
                fn () => $this->hasPasskeys && filament()->auth()->user()->can('upgradeToPasskey', $this->webauthnKey)
            )
            ->extraAttributes([
                'title' => '',
                'x-on:click' => new HtmlString(<<<JS
                \$dispatch('{$eventName}', { id: {$keyId} });
                JS),
            ]);
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.two-factor-authentication.webauthn-key';
    }
}
