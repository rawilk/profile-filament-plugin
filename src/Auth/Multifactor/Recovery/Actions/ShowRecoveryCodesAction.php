<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Js;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Enums\RecoveryCodeSession;

class ShowRecoveryCodesAction
{
    public static function make(string $actionName): Action
    {
        return Action::make($actionName)
            ->modalHeading(__('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.modal.heading'))
            ->modalDescription(str(__('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.modal.description'))->inlineMarkdown()->toHtmlString())
            ->modalWidth(Width::ExtraLarge)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalSubmitAction(
                fn (Action $action) => $action
                    ->label(__('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.modal.actions.submit.label'))
                    ->extraAttributes(['class' => 'w-full'])
            )
            ->cancelParentActions()
            ->schema(fn (array $arguments) => [
                View::make('profile-filament::components.multi-factor.recovery-codes')
                    ->viewData([
                        'recoveryCodes' => Crypt::decrypt($arguments['encrypted'])['recoveryCodes'],
                        'actions' => [
                            Action::make('copy')
                                ->label(__('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.modal.actions.copy.label'))
                                ->icon(Heroicon::OutlinedClipboardDocument)
                                ->color('neutral')
                                ->size(Size::Large)
                                ->alpineClickHandler('
                                    window.navigator.clipboard.writeText(' . Js::from(implode(PHP_EOL, Crypt::decrypt($arguments['encrypted'])['recoveryCodes'])) . ');
                                    $tooltip(' . Js::from(__('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.messages.copied')) . ', {
                                        theme: $store.theme,
                                    });
                                    ')
                                ->extraAttributes(['class' => 'pf-flat-button-neutral']),

                            Action::make('download')
                                ->label(__('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.modal.actions.download.label'))
                                ->icon(Heroicon::ArrowDownTray)
                                ->color('neutral')
                                ->size(Size::Large)
                                ->url('data:application/octet-stream,' . urlencode(implode(PHP_EOL, Crypt::decrypt($arguments['encrypted'])['recoveryCodes'])))
                                ->extraAttributes(['download' => 'recovery-codes.txt', 'class' => 'pf-flat-button-neutral']),
                        ],
                    ]),

                Checkbox::make('confirm_download')
                    ->label(__('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.modal.form.confirm.label'))
                    ->accepted()
                    ->validationMessages([
                        'accepted' => __('profile-filament::auth/multi-factor/recovery/actions/show-new-recovery-codes.modal.form.confirm.messages.accepted'),
                    ]),
            ])
            ->after(function () {
                RecoveryCodeSession::SettingUp->forget();
            });
    }
}
