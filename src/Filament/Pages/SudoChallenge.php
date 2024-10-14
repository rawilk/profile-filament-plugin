<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Livewire\Sudo\Concerns\HasSudoChallengeForm;

use function Filament\Support\get_color_css_variables;

class SudoChallenge extends SimplePage
{
    use HasSudoChallengeForm;
    use WithRateLimiting;

    protected static string $view = 'profile-filament::pages.sudo-challenge';

    public static function setLayout(string $layout): void
    {
        static::$layout = $layout;
    }

    public function mount(): void
    {
        if ($this->sudoModeIsActive()) {
            redirect()->intended(filament()->getHomeUrl() ?? filament()->getUrl());
        }

        $this->mode = ProfileFilament::preferredSudoChallengeMethodFor($this->user, $this->challengeOptions);
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::messages.sudo_challenge.title');
    }

    public function getHeading(): string|Htmlable
    {
        $heading = __('profile-filament::messages.sudo_challenge.title');
        $colors = get_color_css_variables('primary', [100, 400, 500, 600]);

        return new HtmlString(Blade::render(<<<HTML
        <div class="mb-2 mt-4 flex items-center justify-center">
            <div class="rounded-full fi-color-custom bg-custom-100 dark:bg-custom-500/20 p-3" style="{$colors}">
                <x-filament::icon
                    icon="heroicon-m-finger-print"
                    alias="sudo::challenge"
                    class="fi-modal-icon fi-sudo-challenge-heading-icon h-6 w-6 text-custom-600 dark:text-custom-400"
                />
            </div>
        </div>

        <div>
            {$heading}
        </div>
        HTML));
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema(fn () => match ($this->challengeMode) {
                default => [],
                SudoChallengeMode::Password => $this->passwordSchema(),
                SudoChallengeMode::App => $this->authenticatorAppSchema(),
            });
    }

    public function confirm(Request $request, ?array $assertion = null): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getThrottledNotification($exception)?->send();

            return;
        }

        try {
            $this->confirmIdentity($assertion);
        } catch (Halt) {
            return;
        }

        Sudo::activate();
        SudoModeActivated::dispatch($this->user, $request);

        redirect()->intended(filament()->getHomeUrl() ?? filament()->getUrl());
    }

    protected function sudoModeIsActive(): bool
    {
        return Sudo::isActive();
    }

    protected function getThrottledNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]))
            ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]) : null)
            ->danger();
    }
}
