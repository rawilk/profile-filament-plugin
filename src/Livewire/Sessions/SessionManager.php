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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Filament\Actions\Sessions\RevokeAllSessionsInfolistAction;
use Rawilk\ProfileFilament\Filament\Actions\Sessions\RevokeSessionAction;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Support\Agent;

/**
 * @property-read bool $isUsingDatabaseDriver
 * @property-read Collection $sessions
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
        )->map(function ($session) {
            return (object) [
                'id' => Crypt::encryptString($session->id),
                'agent' => $this->createAgent($session),
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === session()->getId(),
                'last_active' => Date::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        });
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

    protected function createAgent(object $session): Agent
    {
        return tap(new Agent, fn (Agent $agent) => $agent->setUserAgent($session->user_agent));
    }

    protected function sessionsDb(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Filament::auth()->id());
    }
}
