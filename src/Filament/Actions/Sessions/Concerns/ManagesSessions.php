<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sessions\Concerns;

use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Rawilk\FilamentPasswordInput\Password;

trait ManagesSessions
{
    protected function isUsingDatabaseDriver(): bool
    {
        return config('session.driver') === 'database';
    }

    protected function getPasswordInput(): Component
    {
        return Password::make('password')
            ->label(__('profile-filament::pages/sessions.manager.password_input_label'))
            ->helperText(__('profile-filament::pages/sessions.manager.password_input_helper'))
            ->currentPassword()
            ->required();
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

    protected function table(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Filament::auth()->id());
    }
}
