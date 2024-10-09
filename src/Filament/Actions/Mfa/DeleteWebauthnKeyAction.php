<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Mfa;

use Filament\Actions\DeleteAction;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Rawilk\ProfileFilament\Contracts\Webauthn\DeleteWebauthnKeyAction as Deleter;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class DeleteWebauthnKeyAction extends DeleteAction
{
    use RequiresSudo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (WebauthnKey $record): string => __('profile-filament::pages/security.mfa.webauthn.actions.delete.trigger_label', ['name' => e($record->name)]));

        $this->icon(FilamentIcon::resolve('actions::delete-action') ?? 'heroicon-o-trash');

        $this->button();

        $this->hiddenLabel();

        $this->tooltip(__('profile-filament::pages/security.mfa.webauthn.actions.delete.trigger_tooltip'));

        $this->size(ActionSize::Small);

        $this->outlined();

        $this->modalHeading(__('profile-filament::pages/security.mfa.webauthn.actions.delete.title'));

        $this->modalSubmitActionLabel(__('filament-actions::delete.single.modal.actions.delete.label'));

        $this->modalDescription(
            fn (WebauthnKey $record): Htmlable => new HtmlString(Blade::render(<<<'HTML'
            <div class="fi-modal-description text-sm text-gray-500 dark:text-gray-400 text-center text-balance space-y-3">
                {{
                    str(__('profile-filament::pages/security.mfa.webauthn.actions.delete.description', [
                        'name' => e($record->name),
                    ]))
                        ->markdown()
                        ->toHtmlString()
                }}
            </div>
            HTML, ['record' => $record]))
        );

        $this->using(function (WebauthnKey $record, Deleter $deleter): bool {
            $deleter($record);

            return true;
        });

        $this->authorize('delete');

        $this->extraAttributes([
            'title' => '',
        ], merge: true);

        $this->before(function (Component $livewire) {
            $this->ensureSudoIsActive($livewire);
        });

        $this->mountUsing(function (Component $livewire) {
            $this->mountSudoAction($livewire);
        });

        $this->registerModalActions([
            $this->getSudoChallengeAction(),
        ]);
    }
}
