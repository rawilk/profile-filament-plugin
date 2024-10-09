<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns;

use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

trait ChallengesSudo
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation(fn () => $this->sudoModeIsAllowed() && (! $this->sudoModeIsActive()));

        $this->modalHeading(__('profile-filament::messages.sudo_challenge.title'));

        $this->modalDescription(null);

        $this->modalIcon(FilamentIcon::resolve('sudo::challenge') ?? 'heroicon-m-finger-print');

        $this->modalSubmitAction(false);

        $this->modalCancelAction(false);

        // I haven't found a way to prevent the normal event yet, so I'm
        // disabling it for now.
        $this->closeModalByClickingAway(false);

        $this->modalWidth(MaxWidth::Large);

        $this->cancelParentActions();

        $this->extraModalWindowAttributes(fn (array $arguments) => [
            'x-on:close-modal.stop' => <<<JS
            () => {
                {$this->getUnmountAction()}
            }
            JS,

            'x-on:sudo-active.stop' => <<<JS
            () => {
                {$this->getSudoActiveAction($arguments)}
            }
            JS,
        ], merge: true);

        $this->modalContent(function () {
            return new HtmlString(Blade::render(<<<'HTML'
            @livewire('sudo-challenge-action-form', [
                'actionType' => $actionType,
            ])
            HTML, [
                'actionType' => $this->getActionType(),
            ]));
        });

        $this->mountUsing(function (Request $request, array $arguments) {
            if ($this->sudoModeIsActive()) {
                Sudo::extend();

                $this->cancel();
            }

            SudoModeChallenged::dispatch(filament()->auth()->user(), $request);
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'sudoChallenge';
    }

    protected function sudoModeIsAllowed(): bool
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->hasSudoMode();
    }

    protected function sudoModeIsActive(): bool
    {
        return Sudo::isActive();
    }

    protected function getActionType(): string
    {
        return match (true) {
            $this instanceof InfolistAction => 'infolist',
            $this instanceof TableAction => 'table',
            default => 'action',
        };
    }

    protected function getUnmountAction(): string
    {
        return match ($this->getActionType()) {
            'infolist' => '$wire.unmountInfolistAction()',
            'table' => '$wire.unmountTableAction()',
            default => '$wire.unmountAction()',
        };
    }

    protected function getSudoActiveAction(array $arguments = []): string
    {
        $actionType = $this->getActionType();

        if (data_get($arguments, 'hasParentModal', true) === false) {
            $name = data_get($arguments, '_action');
            $args = Js::from(Arr::except($arguments, ['hasParentModal', '_action']));

            return match ($actionType) {
                'infolist' => <<<JS
                \$wire.unmountInfolistAction();
                \$wire.mountInfolistAction('{$name}', {$args});
                JS,

                'table' => <<<JS
                \$wire.unmountTableAction();
                \$wire.mountTableAction('{$name}', {$args});
                JS,

                default => <<<JS
                \$wire.unmountAction();
                \$wire.mountAction('{$name}', {$args});
                JS,
            };
        }

        return match ($actionType) {
            'infolist' => '$wire.unmountInfolistAction(false)',
            'table' => '$wire.unmountTableAction(false)',
            default => '$wire.unmountAction(false)',
        };
    }
}
