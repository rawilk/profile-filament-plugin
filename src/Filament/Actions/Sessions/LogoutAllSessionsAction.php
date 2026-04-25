<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sessions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\CurrentPasswordInput;

class LogoutAllSessionsAction extends Action
{
    use CanCustomizeProcess;
    use Concerns\ManagesSessions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->color('danger');

        $this->link();

        $this->label(__('profile-filament::pages/sessions.manager.actions.revoke_all.trigger'));

        $this->modalWidth(Width::Large);

        $this->modalSubmitActionLabel(__('profile-filament::pages/sessions.manager.actions.revoke_all.submit_button'));

        $this->modalHeading(__('profile-filament::pages/sessions.manager.actions.revoke_all.modal_title'));

        $this->modalDescription(str('<span aria-hidden="true"></span>')->inlineMarkdown()->toHtmlString());

        $this->modalIcon(ProfileFilamentIcon::LogoutSessionModalIcon->resolve());

        $this->successNotificationTitle(__('profile-filament::pages/sessions.manager.actions.revoke_all.success'));

        $this->schema([
            CurrentPasswordInput::make('password')
                ->label(__('profile-filament::pages/sessions.manager.password_input_label'))
                ->helperText(__('profile-filament::pages/sessions.manager.password_input_helper')),
        ]);

        $this->action(function (Component $livewire) {
            $result = $this->process(function (array $data) {
                Auth::logoutOtherDevices($data['password']);

                $this->deleteOtherSessions();

                $this->rehashSession();
            });

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
        return 'logoutAllSessions';
    }
}
