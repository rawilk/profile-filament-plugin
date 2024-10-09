<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sessions\Concerns;

use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;

trait RevokesAllSessions
{
    use CanCustomizeProcess;
    use ManagesSessions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->color('danger');

        $this->label(__('profile-filament::pages/sessions.manager.actions.revoke_all.trigger'));

        $this->modalWidth(MaxWidth::Large);

        $this->modalSubmitActionLabel(__('profile-filament::pages/sessions.manager.actions.revoke_all.submit_button'));

        $this->modalHeading(__('profile-filament::pages/sessions.manager.actions.revoke_all.modal_title'));

        $this->modalDescription(null);

        $this->modalIcon('heroicon-o-signal');

        $this->form([
            $this->getPasswordInput(),
        ]);

        $this->successNotificationTitle(__('profile-filament::pages/sessions.manager.actions.revoke_all.success'));

        $this->action(function () {
            $result = $this->process(function (array $data) {
                Auth::logoutOtherDevices($data['password']);

                $this->deleteOtherSessions();

                $this->rehashSession();
            });

            if ($result === false) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'revokeAllSessions';
    }

    protected function deleteOtherSessions(): void
    {
        if (! $this->isUsingDatabaseDriver()) {
            return;
        }

        $this->table()
            ->where('id', '!=', session()->getId())
            ->delete();
    }
}
