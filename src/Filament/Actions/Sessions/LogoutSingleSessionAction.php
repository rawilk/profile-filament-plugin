<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sessions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Facades\Filament;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\CurrentPasswordInput;

class LogoutSingleSessionAction extends Action
{
    use CanCustomizeProcess;
    use Concerns\ManagesSessions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->label(__('profile-filament::pages/sessions.manager.actions.revoke.trigger'));

        $this->color('danger');

        $this->size(Size::Small);

        $this->modalSubmitActionLabel(__('profile-filament::pages/sessions.manager.actions.revoke.submit_button'));

        $this->successNotificationTitle(__('profile-filament::pages/sessions.manager.actions.revoke.success'));

        $this->modalDescription(str('<span aria-hidden="true"></span>')->inlineMarkdown()->toHtmlString());

        $this->modalIcon(FilamentIcon::resolve(ProfileFilamentIcon::LogoutSessionModalIcon->value) ?? Heroicon::OutlinedSignal);

        $this->schema([
            CurrentPasswordInput::make('password')
                ->label(__('profile-filament::pages/sessions.manager.password_input_label'))
                ->helperText(__('profile-filament::pages/sessions.manager.password_input_helper')),
        ]);

        $this->action(function (array $arguments, Component $livewire) {
            $result = $this->process(function (array $data, ?string $sessionId) {
                if (! $sessionId) {
                    return false;
                }

                if (! $this->isUsingDatabaseDriver()) {
                    return true;
                }

                /** @var \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable $user */
                $user = Filament::auth()->user();

                $password = Config::boolean('profile-filament.hash_user_passwords')
                    ? Hash::make($data['password'])
                    : $data['password'];

                $user->forceFill([
                    $user->getAuthPasswordName() => $password,
                ])->saveQuietly();

                $this->rehashSession();
                $this->deleteSessionById($sessionId);

                return true;
            }, [
                'sessionId' => rescue(
                    fn () => Crypt::decryptString(data_get($arguments, 'record')),
                    report: false,
                ),
            ]);

            if ($result === false) {
                $this->failure();

                return;
            }

            $livewire->dispatch('session-logged-out')->self();

            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'logoutSingleSession';
    }
}
