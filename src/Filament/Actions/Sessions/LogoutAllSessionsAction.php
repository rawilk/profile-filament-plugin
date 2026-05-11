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

        $this->label(__('profile-filament::pages/sessions/actions/logout-all.label'));

        $this->modalWidth(Width::Large);

        $this->modalSubmitActionLabel(__('profile-filament::pages/sessions/actions/logout-all.modal.actions.submit.label'));

        $this->modalHeading(__('profile-filament::pages/sessions/actions/logout-all.modal.heading'));

        $this->modalDescription(str('<span aria-hidden="true"></span>')->inlineMarkdown()->toHtmlString());

        $this->modalIcon(ProfileFilamentIcon::LogoutSessionModalIcon->resolve());

        $this->successNotificationTitle(__('profile-filament::pages/sessions/actions/logout-all.notifications.success.title'));

        $this->schema([
            CurrentPasswordInput::make('password')
                ->label(__('profile-filament::pages/sessions/actions/logout-all.modal.form.password.label'))
                ->validationAttribute(__('profile-filament::pages/sessions/actions/logout-all.modal.form.password.validation-attribute'))
                ->helperText(__('profile-filament::pages/sessions/actions/logout-all.modal.form.password.help')),
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
