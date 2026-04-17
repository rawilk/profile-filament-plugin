<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Sessions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Schema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Rawilk\ProfileFilament\Dto\Sessions\Session;
use Rawilk\ProfileFilament\Filament\Actions\Sessions\LogoutAllSessionsAction;
use Rawilk\ProfileFilament\Filament\Actions\Sessions\LogoutSingleSessionAction;
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\SessionManagerInfolist;
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

    public function logoutSingleSessionAction(): Action
    {
        return LogoutSingleSessionAction::make();
    }

    public function infolist(Schema $schema): Schema
    {
        return SessionManagerInfolist::configure(
            schema: $schema,
            sessions: $this->sessions,
            logoutSingleSessionAction: $this->logoutSingleSessionAction(),
            logoutAllSessionsAction: LogoutAllSessionsAction::make(),
        );
    }

    #[On('session-logged-out')]
    public function onLoggedOut(): void
    {
        unset($this->sessions);
    }

    protected function sessionsDb(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Filament::auth()->id());
    }
}
