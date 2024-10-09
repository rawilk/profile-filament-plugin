<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\Passkeys\DeletePasskeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyUpdated;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\DeleteWebauthnKeyAction;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\EditWebauthnKeyAction;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class Passkey extends ProfileComponent
{
    use UsesSudoChallengeAction;

    #[Locked]
    public ?WebauthnKey $passkey;

    public function render(): string
    {
        return <<<'HTML'
        <div @class(['hidden' => ! $passkey])>
            @if ($passkey)
                <div class="flex justify-between gap-x-3">
                    <div class="flex-1 flex items-start">
                        <div class="shrink-0 text-gray-500 dark:text-white">
                            <x-filament::icon
                                class="w-6 h-6"
                                icon="pf-passkey"
                                alias="profile-filament::passkey-item-icon"
                            />
                        </div>

                        <div class="flex-1 ml-4">
                            <div>
                                <span class="text-sm font-semibold">{{ $passkey->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $passkey->registered_at }}
                                </span>
                            </div>

                            <div>
                                <span class="text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $passkey->last_used }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- actions --}}
                    <div class="flex items-center gap-x-2 shrink-0">
                        @can('update', $passkey)
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

    public function editAction(): EditAction
    {
        return EditWebauthnKeyAction::make()
            ->record($this->passkey)
            ->tooltip(__('profile-filament::pages/security.passkeys.actions.edit.trigger_tooltip'))
            ->modalHeading(__('profile-filament::pages/security.passkeys.actions.edit.title'))
            ->successNotificationTitle(__('profile-filament::pages/security.passkeys.actions.edit.success_notification'))
            ->modifyNameInputUsing(function (TextInput $component): TextInput {
                return $component
                    ->label(__('profile-filament::pages/security.passkeys.actions.edit.name'))
                    ->placeholder(__('profile-filament::pages/security.passkeys.actions.edit.name_placeholder'));
            })
            ->after(function (WebauthnKey $record) {
                PasskeyUpdated::dispatch($record, filament()->auth()->user());
            });
    }

    public function deleteAction(): Action
    {
        return DeleteWebauthnKeyAction::make()
            ->record($this->passkey)
            ->tooltip(__('profile-filament::pages/security.passkeys.actions.delete.trigger_tooltip'))
            ->modalHeading(__('profile-filament::pages/security.passkeys.actions.delete.title'))
            ->modalDescription(
                fn (WebauthnKey $record): Htmlable => new HtmlString(Blade::render(<<<'HTML'
                <div class="fi-modal-description text-sm text-gray-500 dark:text-gray-400 text-left text-pretty space-y-3">
                    {{
                        str(__('profile-filament::pages/security.passkeys.actions.delete.description', [
                            'name' => e($record->name),
                        ]))
                            ->markdown()
                            ->toHtmlString()
                    }}
                </div>
                HTML, ['record' => $record]))
            )
            ->using(function (WebauthnKey $record, DeletePasskeyAction $deleter): bool {
                $deleter($record);

                return true;
            })
            ->after(function (WebauthnKey $record): void {
                $this->dispatch(MfaEvent::PasskeyDeleted->value, id: $record->getKey());

                $this->passkey = null;
            });
    }
}
