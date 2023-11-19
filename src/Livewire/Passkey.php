<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\Passkeys\DeletePasskeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyUpdated;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class Passkey extends ProfileComponent
{
    use UsesSudoChallengeAction;

    public ?WebauthnKey $passkey;

    public function messages(): array
    {
        return [
            'mountedActionsData.0.name.unique' => __('profile-filament::pages/security.passkeys.unique_validation_error'),
        ];
    }

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->label(__('profile-filament::pages/security.passkeys.actions.edit.trigger_label', ['name' => e($this->passkey->name)]))
            ->record($this->passkey)
            ->icon(FilamentIcon::resolve('actions::edit-action') ?? 'heroicon-o-pencil')
            ->button()
            ->hiddenLabel()
            ->color('primary')
            ->size('sm')
            ->outlined()
            ->tooltip(__('profile-filament::pages/security.passkeys.actions.edit.trigger_tooltip'))
            ->before(function (EditAction $action, WebauthnKey $record) {
                $this->authorize('edit', $record);
            })
            ->after(function (WebauthnKey $record) {
                PasskeyUpdated::dispatch($record, filament()->auth()->user());
            })
            ->form([
                TextInput::make('name')
                    ->label(__('profile-filament::pages/security.passkeys.actions.edit.name'))
                    ->placeholder(__('profile-filament::pages/security.passkeys.actions.edit.name_placeholder'))
                    ->autofocus()
                    ->required()
                    ->maxlength(255)
                    ->unique(
                        table: config('profile-filament.table_names.webauthn_key'),
                        ignorable: $this->passkey,
                        modifyRuleUsing: function (Unique $rule) {
                            $rule->where('user_id', filament()->auth()->id());
                        },
                    )
                    ->autocomplete('off'),
            ])
            ->modalHeading(__('profile-filament::pages/security.passkeys.actions.edit.title'))
            ->successNotificationTitle(__('profile-filament::pages/security.passkeys.actions.edit.success_notification'))
            ->extraAttributes([
                'title' => '',
            ]);
    }

    public function deleteAction(): Action
    {
        $description = Str::markdown(__('profile-filament::pages/security.passkeys.actions.delete.description', ['name' => e($this->passkey->name)]));

        return Action::make('delete')
            ->label(__('profile-filament::pages/security.passkeys.actions.delete.trigger_label', ['name' => e($this->passkey->name)]))
            ->icon(FilamentIcon::resolve('actions::delete-action') ?? 'heroicon-o-trash')
            ->button()
            ->hiddenLabel()
            ->tooltip(__('profile-filament::pages/security.passkeys.actions.delete.trigger_tooltip'))
            ->color('danger')
            ->size('sm')
            ->outlined()
            ->action(function () {
                $this->ensureSudoIsActive(returnAction: 'delete');
                $this->authorize('delete', $this->passkey);

                app(DeletePasskeyAction::class)($this->passkey);

                Notification::make()
                    ->title(__('profile-filament::pages/security.passkeys.actions.delete.success_message', ['name' => e($this->passkey->name)]))
                    ->success()
                    ->send();

                $this->dispatch(MfaEvent::PasskeyDeleted->value, id: $this->passkey->id);

                $this->passkey = null;
            })
            ->requiresConfirmation()
            ->modalIcon(fn () => FilamentIcon::resolve('actions::delete-action.modal') ?? 'heroicon-o-trash')
            ->modalHeading(__('profile-filament::pages/security.passkeys.actions.delete.title'))
            ->modalDescription(
                new HtmlString(<<<HTML
                <div class="text-sm text-left space-y-2">
                    {$description}
                </div>
                HTML)
            )
            ->modalSubmitActionLabel(__('profile-filament::pages/security.passkeys.actions.delete.confirm'))
            ->extraAttributes([
                'title' => '',
            ])
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'delete');
            });
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.passkey';
    }
}
