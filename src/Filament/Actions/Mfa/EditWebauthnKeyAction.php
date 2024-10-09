<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Mfa;

use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Validation\Rules\Unique;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpdated;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class EditWebauthnKeyAction extends EditAction
{
    protected ?Closure $modifyNameInputCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (WebauthnKey $record): string => __('profile-filament::pages/security.mfa.webauthn.actions.edit.trigger_label', ['name' => e($record->name)]));

        $this->icon(FilamentIcon::resolve('actions::edit-action') ?? 'heroicon-o-pencil');

        $this->button();

        $this->hiddenLabel();

        $this->color('primary');

        $this->size(ActionSize::Small);

        $this->modalWidth(MaxWidth::Large);

        $this->outlined();

        $this->tooltip(__('profile-filament::pages/security.mfa.webauthn.actions.edit.trigger_tooltip'));

        $this->authorize('update');

        $this->modalHeading(__('profile-filament::pages/security.mfa.webauthn.actions.edit.title'));

        $this->successNotificationTitle(__('profile-filament::pages/security.mfa.webauthn.actions.edit.success_message'));

        $this->after(function (WebauthnKey $record) {
            WebauthnKeyUpdated::dispatch($record, filament()->auth()->user());
        });

        $this->form(fn (): array => [
            $this->getNameInput(),
        ]);

        $this->extraAttributes([
            'title' => '',
        ]);
    }

    public function modifyNameInputUsing(Closure $callback): static
    {
        $this->modifyNameInputCallback = $callback;

        return $this;
    }

    protected function getNameInput(): TextInput
    {
        $input = TextInput::make('name')
            ->label(__('profile-filament::pages/security.mfa.webauthn.actions.edit.name'))
            ->placeholder(__('profile-filament::pages/security.mfa.webauthn.actions.edit.name_placeholder'))
            ->required()
            ->maxLength(255)
            ->autocomplete('off')
            ->unique(
                table: config('profile-filament.table_names.webauthn_key'),
                ignoreRecord: true,
                modifyRuleUsing: function (Unique $rule) {
                    $rule->where('user_id', filament()->auth()->id());
                },
            )
            ->validationMessages([
                'unique' => __('profile-filament::pages/security.passkeys.unique_validation_error'),
            ]);

        if (is_callable($this->modifyNameInputCallback)) {
            $input = $this->evaluate($this->modifyNameInputCallback, [
                'component' => $input,
            ]) ?? $input;
        }

        return $input;
    }
}
