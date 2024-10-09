<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Mfa;

use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Validation\Rules\Unique;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUpdated;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class EditAuthenticatorAppAction extends EditAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (AuthenticatorApp $record): string => __('profile-filament::pages/security.mfa.app.actions.edit.trigger_label', [
            'name' => e($record->name),
        ]));

        $this->icon(FilamentIcon::resolve('actions::edit-action') ?? 'heroicon-o-pencil');

        $this->button();

        $this->hiddenLabel();

        $this->color('primary');

        $this->size(ActionSize::Small);

        $this->outlined();

        $this->tooltip(__('profile-filament::pages/security.mfa.app.actions.edit.trigger_tooltip'));

        $this->authorize('update');

        $this->modalHeading(__('profile-filament::pages/security.mfa.app.actions.edit.title'));

        $this->successNotificationTitle(__('profile-filament::pages/security.mfa.app.actions.edit.success_message'));

        $this->modalWidth(MaxWidth::Large);

        $this->form([
            TextInput::make('name')
                ->label(__('profile-filament::pages/security.mfa.app.actions.edit.name'))
                ->autofocus()
                ->required()
                ->maxLength(255)
                ->autocomplete('off')
                ->unique(
                    table: config('profile-filament.table_names.authenticator_app'),
                    ignoreRecord: true,
                    modifyRuleUsing: function (Unique $rule) {
                        $rule->where('user_id', filament()->auth()->id());
                    },
                )
                ->helperText(__('profile-filament::pages/security.mfa.app.actions.edit.name_help')),
        ]);

        $this->after(function (AuthenticatorApp $record) {
            TwoFactorAppUpdated::dispatch($record, filament()->auth()->user());
        });

        $this->extraAttributes([
            'title' => '',
        ]);
    }
}
