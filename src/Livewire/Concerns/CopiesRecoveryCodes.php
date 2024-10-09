<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Concerns;

use Filament\Actions\Action;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * @property-read array $recoveryCodes
 */
trait CopiesRecoveryCodes
{
    #[Computed]
    public function recoveryCodes(): array
    {
        return filament()->auth()->user()->recoveryCodes();
    }

    public function copyAction(): Action
    {
        return Action::make('copy')
            ->color('gray')
            ->label(__('profile-filament::pages/security.mfa.recovery_codes.actions.copy.label'))
            ->icon(FilamentIcon::resolve('mfa::recovery-codes.copy') ?? 'heroicon-o-document-duplicate')
            ->alpineClickHandler(Blade::render(<<<'JS'
                window.navigator.clipboard.writeText(@js(implode(PHP_EOL, $codes)));
                $tooltip(@js($message), { theme: $store.theme });
            JS, [
                'message' => __('profile-filament::pages/security.mfa.recovery_codes.actions.copy.confirmation'),
                'codes' => $this->recoveryCodes,
            ]));
    }

    public function downloadAction(): Action
    {
        return Action::make('download')
            ->color('gray')
            ->label(__('profile-filament::pages/security.mfa.recovery_codes.actions.download.label'))
            ->icon(FilamentIcon::resolve('mfa::recovery-codes.download') ?? 'heroicon-o-arrow-down-tray')
            ->action(function () {
                $appName = Str::slug(config('app.name'));

                return response()->streamDownload(function () {
                    echo implode(PHP_EOL, $this->recoveryCodes);
                }, "{$appName}-recovery-codes.txt");
            });
    }

    public function printAction(): Action
    {
        return Action::make('print')
            ->color('gray')
            ->label(__('profile-filament::pages/security.mfa.recovery_codes.actions.print.label'))
            ->icon(FilamentIcon::resolve('mfa::recovery-codes.print') ?? 'heroicon-o-printer')
            ->alpineClickHandler(Blade::render(<<<'JS'
                const newTab = window.open('', '_blank');
                const uri = @js($url);

                fetch(uri)
                    .then(response => response.text())
                    .then(htmlContent => {
                        newTab.document.write(htmlContent);

                        setTimeout(function () {
                            newTab.print();

                            newTab.window.onfocus = () => newTab.close();
                        }, 50);
                    })
                    .catch(() => alert('An error occurred while trying to print your recovery codes.'));
            JS, [
                'url' => route('filament.' . filament()->getId() . '.auth.mfa.recovery-codes.print'),
            ]));
    }
}
