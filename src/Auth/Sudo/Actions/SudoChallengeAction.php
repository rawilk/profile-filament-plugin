<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Auth\Sudo\Concerns\InteractsWithSudo;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;

class SudoChallengeAction extends Action
{
    use InteractsWithSudo;

    protected ?Closure $executeAfterSudoCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->modalSubmitAction(false);
        $this->modalCancelAction(false);
        $this->closeModalByClickingAway(false);
        $this->closeModalByEscaping(false);

        $this->cancelParentActions();

        $this->modalHeading(__('profile-filament::auth/sudo/sudo.challenge.heading'));

        $this->modalDescription(str('<span aria-hidden="true"></span>')->inlineMarkdown()->toHtmlString());

        $this->modalIcon(FilamentIcon::resolve(ProfileFilamentIcon::SudoChallenge->value) ?? Heroicon::FingerPrint);
        $this->modalIconColor('primary');

        $this->extraModalWindowAttributes([
            'class' => 'pf-sudo-form pf-sudo-modal-content',

            'x-on:close-modal.prevent.stop' => <<<'JS'
            $wire.unmountAction()
            JS,

            'x-on:sudo-confirmed.stop' => <<<'JS'
            $wire.callMountedAction()
            JS,
        ], merge: true);

        $this->schema([
            Text::make(
                new HtmlString(Blade::render(<<<'HTML'
                <x-profile-filament::sudo.signed-in-as
                    :user-handle="filament()->auth()->user()->email"
                />
                HTML))
            ),

            Livewire::make('sudo-challenge-action-form'),

            Text::make(
                new HtmlString(Blade::render(<<<'HTML'
                <div class="pf-sudo-tip">{{ str(__('profile-filament::auth/sudo/sudo.challenge.tip'))->inlineMarkdown()->toHtmlString() }}</div>
                HTML))
            )
                ->size(Size::ExtraSmall)
                ->color('neutral'),
        ]);

        $this->action(function (HasActions $livewire, array $arguments) {
            if (! $this->sudoModeIsActive()) {
                return;
            }

            // Useful for certain actions that have mount actions that require sudo mode to be active first.
            if ($this->executeAfterSudoCallback) {
                $this->evaluate($this->executeAfterSudoCallback);
            }

            // This is for sensitive actions that do not require a modal.
            if (Arr::has($arguments, 'sudo.action')) {
                $livewire->replaceMountedAction($arguments['sudo']['action'], arguments: $arguments, context: $arguments['sudo']['context'] ?? []);
            }

            // The SudoChallengeActionForm handles activating Sudo mode,
            // so we just need to mount the sensitive action now.
            $livewire->unmountAction(false);
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'sudoChallenge';
    }

    public function executeAfterSudo(?Closure $callback): static
    {
        $this->executeAfterSudoCallback = $callback;

        return $this;
    }
}
