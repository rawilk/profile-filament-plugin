<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Infolists;

use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Rawilk\ProfileFilament\Concerns\HasSimpleClosureEval;

class SessionManagerInfolist
{
    use Concerns\IsConfigurable;
    use HasSimpleClosureEval;

    public static function configure(
        Schema $schema,
        Collection $sessions,
        Action $logoutSingleSessionAction,
        Action $logoutAllSessionsAction,
    ): Schema {
        $schema
            ->components(static::resolveComponents($sessions, $logoutSingleSessionAction, $logoutAllSessionsAction));

        if (static::$configureSchemaUsing) {
            static::evaluate(static::$configureSchemaUsing, [
                'sessions' => $sessions,
                'logoutSingleSessionAction' => $logoutSingleSessionAction,
                'logoutAllSessionsAction' => $logoutAllSessionsAction,
            ]);
        }

        return $schema;
    }

    protected static function resolveComponents(
        Collection $sessions,
        Action $logoutSingleSessionAction,
        Action $logoutAllSessionsAction,
    ): array {
        if (static::$getComponentsUsing) {
            return static::evaluate(static::$getComponentsUsing, [
                'sessions' => $sessions,
                'logoutSingleSessionAction' => $logoutSingleSessionAction,
                'logoutAllSessionsAction' => $logoutAllSessionsAction,
            ]);
        }

        return [
            Section::make(__('profile-filament::pages/sessions.manager.heading'))
                ->description(__('profile-filament::pages/sessions.manager.description'))
                ->schema([
                    $logoutAllSessionsAction,

                    View::make('profile-filament::livewire.sessions.session-list')
                        ->viewData([
                            'sessions' => $sessions,
                            'logoutSingleSessionAction' => $logoutSingleSessionAction,
                        ])
                        ->hidden(fn (): bool => $sessions->isEmpty()),
                ]),
        ];
    }
}
