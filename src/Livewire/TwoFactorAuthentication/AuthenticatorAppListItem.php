<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\AuthenticatorApps\DeleteAuthenticatorAppAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUpdated;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class AuthenticatorAppListItem extends ProfileComponent
{
    use UsesSudoChallengeAction;

    public ?AuthenticatorApp $app;

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->label(__('profile-filament::pages/security.mfa.app.actions.edit.trigger_label', ['name' => e($this->app->name)]))
            ->record($this->app)
            ->icon(FilamentIcon::resolve('actions::edit-action') ?? 'heroicon-o-pencil')
            ->button()
            ->hiddenLabel()
            ->color('primary')
            ->size('sm')
            ->outlined()
            ->tooltip(__('profile-filament::pages/security.mfa.app.actions.edit.trigger_tooltip'))
            ->before(function (EditAction $action, AuthenticatorApp $record) {
                $this->authorize('edit', $record);
            })
            ->after(function (AuthenticatorApp $record) {
                TwoFactorAppUpdated::dispatch($record, filament()->auth()->user());
            })
            ->form([
                TextInput::make('name')
                    ->label(__('profile-filament::pages/security.mfa.app.actions.edit.name'))
                    ->autofocus()
                    ->required()
                    ->maxLength(255)
                    ->autocomplete('off')
                    ->unique(
                        table: config('profile-filament.table_names.authenticator_app'),
                        ignorable: $this->app,
                        modifyRuleUsing: function (Unique $rule) {
                            $rule->where('user_id', filament()->auth()->id());
                        },
                    )
                    ->helperText(__('profile-filament::pages/security.mfa.app.actions.edit.name_help')),
            ])
            ->modalHeading(__('profile-filament::pages/security.mfa.app.actions.edit.title'))
            ->successNotificationTitle(__('profile-filament::pages/security.mfa.app.actions.edit.success_message'))
            ->extraAttributes([
                'title' => '',
            ]);
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('profile-filament::pages/security.mfa.app.actions.delete.trigger_label', ['name' => e($this->app->name)]))
            ->icon(FilamentIcon::resolve('actions::delete-action') ?? 'heroicon-o-trash')
            ->button()
            ->hiddenLabel()
            ->tooltip(__('profile-filament::pages/security.mfa.app.actions.delete.trigger_tooltip'))
            ->color('danger')
            ->size('sm')
            ->outlined()
            ->action(function (DeleteAuthenticatorAppAction $deleter) {
                $this->ensureSudoIsActive(returnAction: 'delete');

                $this->authorize('delete', $this->app);

                $deleter($this->app);

                Notification::make()
                    ->title(__('profile-filament::pages/security.mfa.app.actions.delete.success_message', ['name' => e($this->app->name)]))
                    ->success()
                    ->send();

                $this->dispatch(MfaEvent::AppDeleted->value, appId: $this->app->getKey());

                $this->app = null;
            })
            ->requiresConfirmation()
            ->modalHeading(__('profile-filament::pages/security.mfa.app.actions.delete.title'))
            ->modalIcon(fn () => FilamentIcon::resolve('actions::delete-action.modal') ?? 'heroicon-o-trash')
            ->modalDescription(
                new HtmlString(
                    Str::inlineMarkdown(__('profile-filament::pages/security.mfa.app.actions.delete.description', ['name' => e($this->app->name)]))
                )
            )
            ->modalSubmitActionLabel(__('profile-filament::pages/security.mfa.app.actions.delete.confirm'))
            ->extraAttributes([
                'title' => '',
            ])
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'delete');
            });
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.two-factor-authentication.authenticator-app-list-item';
    }
}
