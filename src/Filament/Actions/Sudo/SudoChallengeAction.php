<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sudo;

use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Text;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\Sudo;

/** @deprecated */
class SudoChallengeAction extends Action
{
    use \Rawilk\ProfileFilament\Auth\Sudo\Concerns\InteractsWithSudo;

    protected ?Action $nextAction = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->modalHeading(__('profile-filament::messages.sudo_challenge.title'));

        $this->modalDescription(str('<span aria-hidden="true"></span>')->inlineMarkdown()->toHtmlString());

        $this->modalIcon(FilamentIcon::resolve(ProfileFilamentIcon::SudoChallenge->value) ?? Heroicon::FingerPrint);

        $this->color('primary');

        $this->modalSubmitAction(false);
        $this->modalCancelAction(false);

        $this->schema([
            Text::make(
                new HtmlString(Blade::render(<<<'HTML'
                <x-profile-filament::sudo.signed-in-as
                    :user-handle="filament()->auth()->user()->email"
                />
                HTML))
            ),

            Livewire::make('sudo-challenge-action-form'),
        ]);

        $this->extraModalWindowAttributes([
            'class' => 'pf-sudo-form pf-sudo-modal-content',

            'x-on:sudo-confirmed.stop' => <<<'JS'
            $wire.callMountedAction()
            JS,
        ], merge: true);

        $this->mountUsing(function (Request $request, array $arguments, HasActions $livewire) {
            //            $this->mountNextAction($livewire, $arguments);
            //
            //            return;

            if ($this->shouldChallengeForSudo()) {
                SudoModeChallenged::dispatch(Filament::auth()->user(), $request);

                return;
            }
            //                Sudo::deactivate();
            //                return;

            $this->extendSudo();
            $this->mountNextAction($livewire, $arguments);
        });

        $this->action(function (array $arguments, HasActions $livewire) {
            if (! $this->sudoModeIsActive()) {
                return;
            }

            // Our livewire component activates sudo mode, so we just need
            // to mount the next action now.
            $this->mountNextAction($livewire, $arguments);
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'sudoChallenge';
    }

    public function nextAction(?Action $action): static
    {
        $this->registerModalActions([$action]);

        $this->nextAction = $action;

        return $this;
    }

    public function getNextAction(): ?Action
    {
        return $this->nextAction;
    }

    public function toHtml(): string
    {
        $nextAction = $this->getNextAction();

        if (! $nextAction) {
            return parent::toHtml();
        }

        // Clone the next action to avoid modifying the original instance.
        $renderAction = $nextAction->getClone();

        // Hijack the click events by injecting this action's handlers via extra attributes.
        $renderAction->actionJs('$wire.' . $this->getLivewireClickHandler());

        // Ensure it inherits attributes like 'disabled' if this action is disabled.
        if ($this->isDisabled()) {
            $renderAction->disabled();
        }

        return $renderAction->toHtml();
    }

    public function isDisabled(): bool
    {
        if (parent::isDisabled()) {
            return true;
        }

        return (bool) $this->getNextAction()?->isDisabled();
    }

    public function isHidden(): bool
    {
        if (parent::isHidden()) {
            return true;
        }

        return (bool) $this->getNextAction()?->isHidden();
    }

    protected function mountNextAction(HasActions $livewire, array $arguments): void
    {
        $nextAction = $this->getNextAction();

        if (! $nextAction) {
            return;
        }

        $livewire->mountAction($nextAction->getName(), arguments: $arguments);

        //        $this->getLivewire()->replaceMountedAction($nextAction->getName(), $arguments);
    }
}
