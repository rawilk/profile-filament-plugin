<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Sessions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Infolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Dto\Sessions\Session;
use Rawilk\ProfileFilament\Filament\Actions\Sessions\RevokeAllSessionsInfolistAction;
use Rawilk\ProfileFilament\Filament\Actions\Sessions\RevokeSessionAction;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;

/**
 * @property-read bool $isUsingDatabaseDriver
 * @property-read Collection<int, Session> $sessions
 */
class SessionManager extends ProfileComponent implements HasInfolists
{
    use InteractsWithInfolists;

    #[Computed]
    public function isUsingDatabaseDriver(): bool
    {
        return config('session.driver') === 'database';
    }

    #[Computed]
    public function sessions(): Collection
    {
        if (! $this->isUsingDatabaseDriver) {
            return collect();
        }

        return collect(
            $this->sessionsDb()
                ->orderBy('last_activity', 'desc')
                ->get(),
        )->mapInto(Session::class);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->infolist }}

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('profile-filament::pages/sessions.manager.heading'))
                    ->description(__('profile-filament::pages/sessions.manager.description'))
                    ->schema([
                        Infolists\Components\Actions::make([
                            $this->revokeAllAction(),
                        ]),

                        Infolists\Components\View::make('profile-filament::livewire.sessions.session-list')
                            ->viewData([
                                $this->sessions,
                            ])
                            ->hidden(fn (): bool => $this->sessions->isEmpty()),
                    ]),
            ]);
    }

    public function revokeSessionAction(): Action
    {
        return RevokeSessionAction::make();
    }

    public function revokeAllAction(): Infolists\Components\Actions\Action
    {
        return RevokeAllSessionsInfolistAction::make()
            ->after(function () {
                unset($this->sessions);
            });
    }

    protected function sessionsDb(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Filament::auth()->id());
    }
}
