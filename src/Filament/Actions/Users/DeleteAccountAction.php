<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Users;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;
use Rawilk\ProfileFilament\Contracts\DeleteAccountAction as DeleteAccountActionContract;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\DeleteAccountEmailConfirmationInput;

class DeleteAccountAction extends Action
{
    use CanCustomizeProcess;
    use RequiresSudoChallenge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge();

        $this->requiresConfirmation();

        $this->color('danger');

        $this->label(__('profile-filament::pages/settings.delete_account.actions.delete.trigger'));

        $this->modalSubmitActionLabel(__('profile-filament::pages/settings.delete_account.actions.delete.submit_button'));

        $this->modalIcon(FilamentIcon::resolve('actions::delete-action.modal') ?? Heroicon::OutlinedTrash);

        $this->modalDescription(new HtmlString(Blade::render(<<<'HTML'
        <div class="fi-modal-description text-sm text-gray-500 dark:text-gray-400 text-left text-pretty">
            {{ __('profile-filament::pages/settings.delete_account.description') }}
        </div>
        HTML)));

        $this->schema([
            // Even though we're requiring "sudo" mode to do this, we want the user to enter their email
            // address, so they're more likely to be conscious of the action they're taking.
            DeleteAccountEmailConfirmationInput::make('email'),
        ]);

        $this->successNotificationTitle(__('profile-filament::pages/settings.delete_account.actions.delete.success'));

        $this->successRedirectUrl(fn () => Filament::getLoginUrl());

        $this->action(function (DeleteAccountActionContract $deleter) {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }

            $result = $this->process(function (DeleteAccountActionContract $deleter) {
                $deleter(Filament::auth()->user());

                return true;
            }, ['deleter' => $deleter]);

            if ($result === false) {
                $this->failure();

                return;
            }

            Filament::auth()->logout();

            session()->invalidate();
            session()->regenerateToken();

            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'deleteAccount';
    }
}
