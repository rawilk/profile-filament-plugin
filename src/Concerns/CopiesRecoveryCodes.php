<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns;

use Filament\Actions\Action;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
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
        /** @phpstan-ignore-next-line */
        return filament()->auth()->user()->recoveryCodes();
    }

    public function copyAction(): Action
    {
        $copyMessage = Js::from(__('profile-filament::pages/security.mfa.recovery_codes.actions.copy.confirmation'));
        $codes = Js::from(implode(PHP_EOL, $this->recoveryCodes));

        return Action::make('copy')
            ->livewireClickHandlerEnabled(false)
            ->color('gray')
            ->label(__('profile-filament::pages/security.mfa.recovery_codes.actions.copy.label'))
            ->icon(FilamentIcon::resolve('mfa::recovery-codes.copy') ?? 'heroicon-o-document-duplicate')
            ->extraAttributes([
                'x-on:click' => new HtmlString(<<<JS
                window.navigator.clipboard.writeText({$codes});
                \$tooltip({$copyMessage}, { theme: \$store.theme });
                JS),
            ]);
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
        $url = Js::from(route('filament.' . filament()->getCurrentPanel()->getId() . '.auth.mfa.recovery-codes.print'));

        return Action::make('print')
            ->livewireClickHandlerEnabled(false)
            ->color('gray')
            ->label(__('profile-filament::pages/security.mfa.recovery_codes.actions.print.label'))
            ->icon(FilamentIcon::resolve('mfa::recovery-codes.print') ?? 'heroicon-o-printer')
            ->extraAttributes([
                'x-on:click' => new HtmlString(<<<JS
                const newTab = window.open('', '_blank');
                const uri = {$url};

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
                JS),
            ]);
    }
}
