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
use Rawilk\ProfileFilament\Contracts\AuthenticatorApps\DeleteAuthenticatorAppAction;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class DeleteTotpAction extends DeleteAction
{
    use RequiresSudo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (AuthenticatorApp $record): string => __('profile-filament::pages/security.mfa.app.actions.delete.trigger_label', [
            'name' => e($record->name),
        ]));

        $this->icon(FilamentIcon::resolve('actions::delete-action') ?? 'heroicon-o-trash');

        $this->button();

        $this->outlined();

        $this->size(ActionSize::Small);

        $this->hiddenLabel();

        $this->tooltip(__('profile-filament::pages/security.mfa.app.actions.delete.trigger_tooltip'));

        $this->modalHeading(__('profile-filament::pages/security.mfa.app.actions.delete.title'));

        $this->modalSubmitActionLabel(__('profile-filament::pages/security.mfa.app.actions.delete.confirm'));

        $this->authorize('delete');

        $this->modalDescription(
            fn (AuthenticatorApp $record): Htmlable => new HtmlString(Blade::render(<<<'HTML'
                <div class="fi-modal-description text-sm text-gray-500 dark:text-gray-400 text-center text-balance">
                    {{
                        str(__('profile-filament::pages/security.mfa.app.actions.delete.description', [
                            'name' => e($app->name),
                        ]))
                            ->markdown()
                            ->toHtmlString()
                    }}
                </div>
                HTML, ['app' => $record]))
        );

        $this->using(function (AuthenticatorApp $record, DeleteAuthenticatorAppAction $deleter) {
            $deleter($record);

            return true;
        });

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
