<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Users\Concerns;

use Closure;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;
use Rawilk\ProfileFilament\Contracts\DeleteAccountAction;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;

trait DeletesAccount
{
    use CanCustomizeProcess;
    use RequiresSudo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->color('danger');

        $this->label(__('profile-filament::pages/settings.delete_account.actions.delete.trigger'));

        $this->modalSubmitActionLabel(__('profile-filament::pages/settings.delete_account.actions.delete.submit_button'));

        $this->modalIcon(FilamentIcon::resolve('actions::delete-action.modal') ?? 'heroicon-o-trash');

        $this->modalHeading(__('profile-filament::pages/settings.delete_account.actions.delete.modal_title'));

        $this->modalDescription(new HtmlString(Blade::render(<<<'HTML'
        <div class="fi-modal-description text-sm text-gray-500 dark:text-gray-400 text-left text-pretty">
            {{ __('profile-filament::pages/settings.delete_account.description') }}
        </div>
        HTML)));

        $this->form([
            // Even though we're requiring "sudo" mode to do this, we want the user to enter their email
            // address in, so they're more likely to be conscious of what they're doing.
            $this->getEmailInput(),
        ]);

        $this->before(function (Component $livewire) {
            $this->ensureSudoIsActive($livewire);
        });

        $this->successNotificationTitle(__('profile-filament::pages/settings.delete_account.actions.delete.success'));

        $this->successRedirectUrl(fn () => filament()->getLoginUrl());

        $this->action(function (Form $form, DeleteAccountAction $deleter) {
            $result = $this->process(function () use ($deleter) {
                $deleter(filament()->auth()->user());

                return true;
            }, ['deleter' => $deleter]);

            if ($result === false) {
                $this->failure();

                return;
            }

            filament()->auth()->logout();

            session()->invalidate();
            session()->regenerateToken();

            $this->success();
        });

        $this->mountUsing(function (Component $livewire) {
            $this->mountSudoAction($livewire);
        });

        $this->registerModalActions([
            $this->getSudoChallengeAction(),
        ]);
    }

    public static function getDefaultName(): ?string
    {
        return 'deleteAccount';
    }

    protected function getEmailInput(): TextInput
    {
        return TextInput::make('email')
            ->label(__('profile-filament::pages/settings.delete_account.actions.delete.email_label', [
                'email' => filament()->auth()->user()->email,
            ]))
            ->required()
            ->email()
            ->rules([
                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                    if (Str::lower($value) !== Str::lower(filament()->auth()->user()->email)) {
                        $fail(__('profile-filament::pages/settings.delete_account.actions.delete.incorrect_email'));
                    }
                },
            ]);
    }
}
