<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Sessions;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Rawilk\FilamentPasswordInput\Password;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Support\Agent;
use Throwable;

/**
 * @property-read bool $isUsingDatabaseDriver
 * @property-read Collection $sessions
 */
class SessionManager extends ProfileComponent
{
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

    public function revokeAction(): Action
    {
        return Action::make('revoke')
            ->color('danger')
            ->label(__('profile-filament::pages/sessions.manager.actions.revoke.trigger'))
            ->size('sm')
            ->form([
                $this->getPasswordInput(),
            ])
            ->modalSubmitActionLabel(__('profile-filament::pages/sessions.manager.actions.revoke.submit_button'))
            ->modalWidth('lg')
            ->action(function (array $arguments, Form $form) {
                /** @var string $sessionId */
                $sessionId = rescue(fn () => Crypt::decryptString($arguments['session'] ?? ''));

                if (! $sessionId) {
                    return;
                }

                $this->deleteSessionById($sessionId, $form->getState()['password']);

                Notification::make()
                    ->success()
                    ->title(__('profile-filament::pages/sessions.manager.actions.revoke.success'))
                    ->send();
            });
    }

    public function revokeAllAction(): Action
    {
        return Action::make('revokeAll')
            ->color('danger')
            ->label(__('profile-filament::pages/sessions.manager.actions.revoke_all.trigger'))
            ->size('sm')
            ->form([
                $this->getPasswordInput(),
            ])
            ->modalWidth('lg')
            ->modalSubmitActionLabel(__('profile-filament::pages/sessions.manager.actions.revoke_all.submit_button'))
            ->modalHeading(__('profile-filament::pages/sessions.manager.actions.revoke_all.modal_title'))
            ->action(function (Form $form) {
                Auth::logoutOtherDevices($form->getState()['password']);

                $this->deleteOtherSessions();

                session()->put([
                    "password_hash_{$this->getGuard()}" => Filament::auth()->user()->getAuthPassword(),
                ]);

                Notification::make()
                    ->success()
                    ->title(__('profile-filament::pages/sessions.manager.actions.revoke_all.success'))
                    ->send();
            });
    }

    protected function getPasswordInput(): Component
    {
        return Password::make('password')
            ->label(__('profile-filament::pages/sessions.manager.password_input_label'))
            ->helperText(__('profile-filament::pages/sessions.manager.password_input_helper'))
            ->currentPassword()
            ->required();
    }

    protected function deleteSessionById(string $sessionId, string $password): void
    {
        if (! $this->isUsingDatabaseDriver) {
            return;
        }

        Filament::auth()->user()->forceFill([
            'password' => $password,
        ])->save();

        session()->put([
            "password_hash_{$this->getGuard()}" => Filament::auth()->user()->getAuthPassword(),
        ]);

        $this->sessionsDb()
            ->whereNotIn('id', [session()->getId(), $sessionId])
            ->select(['id', 'payload'])
            ->cursor()
            ->each(function (object $session) {
                try {
                    $payload = unserialize(base64_decode($session->payload));

                    $payload["password_hash_{$this->getGuard()}"] = Filament::auth()->user()->getAuthPassword();

                    $this->sessionsDb()
                        ->where('id', $session->id)
                        ->update([
                            'payload' => base64_encode(serialize($payload)),
                        ]);
                } catch (Throwable) {
                }
            });

        $this->sessionsDb()
            ->where('id', $sessionId)
            ->delete();
    }

    protected function deleteOtherSessions(): void
    {
        if (! $this->isUsingDatabaseDriver) {
            return;
        }

        $this->sessionsDb()
            ->where('id', '!=', session()->getId())
            ->delete();
    }

    protected function createAgent(object $session): Agent
    {
        return tap(new Agent, fn (Agent $agent) => $agent->setUserAgent($session->user_agent));
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.sessions.session-manager';
    }

    protected function sessionsDb(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Filament::auth()->id());
    }

    protected function getGuard(): string
    {
        return Filament::getCurrentPanel()?->getAuthGuard()
            ?? Auth::getDefaultDriver();
    }
}
