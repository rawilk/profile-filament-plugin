<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Mfa;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Livewire\Component;
use Rawilk\ProfileFilament\Contracts\TwoFactor\GenerateNewRecoveryCodesAction as GenerateCodes;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns\RequiresSudo;

class GenerateNewRecoveryCodesAction extends Action
{
    use CanCustomizeProcess;
    use RequiresSudo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->color('gray');

        $this->label(__('profile-filament::pages/security.mfa.recovery_codes.actions.generate.button'));

        $this->modalIcon(FilamentIcon::resolve('mfa::recovery-codes') ?? 'heroicon-o-key');

        $this->modalIconColor('primary');

        $this->successNotification(
            fn (): Notification => Notification::make()
                ->success()
                ->title(__('profile-filament::pages/security.mfa.recovery_codes.actions.generate.success_title'))
                ->body(__('profile-filament::pages/security.mfa.recovery_codes.actions.generate.success_message'))
                ->persistent(),
        );

        $this->action(function (GenerateCodes $generator) {
            $result = $this->process(function () use ($generator) {
                $generator(filament()->auth()->user());
            }, ['generator' => $generator]);

            if ($result === false) {
                $this->failure();

                return;
            }

            $this->success();
        });

        $this->before(function (Component $livewire) {
            $this->ensureSudoIsActive($livewire);
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
        return 'generateNewRecoveryCodes';
    }
}
