<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sessions\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

use function Illuminate\Support\defer;

trait ManagesSessions
{
    protected function isUsingDatabaseDriver(): bool
    {
        return config('session.driver') === 'database';
    }

    protected function getGuard(): string
    {
        return Filament::getCurrentPanel()?->getAuthGuard()
            ?? Auth::getDefaultDriver();
    }

    protected function rehashSession(): void
    {
        session()->put([
            "password_hash_{$this->getGuard()}" => Filament::auth()->user()->getAuthPassword(),
        ]);
    }

    protected function sessionDb(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Filament::auth()->id());
    }

    protected function deleteOtherSessions(): void
    {
        if (! $this->isUsingDatabaseDriver()) {
            return;
        }

        $this->sessionDb()
            ->where('id', '!=', session()->getId())
            ->delete();
    }

    protected function deleteSessionById(string $sessionId): void
    {
        defer(function () use ($sessionId) {
            $newPasswordHash = Filament::auth()->user()->getAuthPassword();
            $guard = $this->getGuard();

            $this->sessionDb()
                ->whereNotIn('id', [session()->getId(), $sessionId])
                ->select(['id', 'payload'])
                ->chunkById(100, function ($sessions) use ($newPasswordHash, $guard) {
                    foreach ($sessions as $session) {
                        try {
                            $payload = unserialize(base64_decode($session->payload));

                            $payload["password_hash_{$guard}"] = $newPasswordHash;

                            $this->sessionDb()
                                ->where('id', $session->id)
                                ->update([
                                    'payload' => base64_encode(serialize($payload)),
                                ]);
                        } catch (Throwable) {
                        }
                    }
                });
        });

        $this->sessionDb()
            ->where('id', $sessionId)
            ->delete();
    }
}
