<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\DeleteTotpAction;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\EditAuthenticatorAppAction;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class AuthenticatorAppListItem extends ProfileComponent
{
    use UsesSudoChallengeAction;

    #[Locked]
    public ?AuthenticatorApp $app;

    public function render(): string
    {
        return <<<'HTML'
        <div
            @class([
                'hidden' => ! $app,
            ])
        >
            @if ($app)
                <div class="py-3 flex justify-between items-center gap-x-3">
                    <div>
                        <div>
                            <span>{{ $app->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">
                                {{ $app->registered_at }}
                            </span>
                        </div>

                        <div>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">
                                {{ $app->last_used }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-x-2">
                    @can('update', $app)
                        {{ $this->editAction }}
                    @endcan

                    {{ $this->deleteAction }}
                </div>
                </div>

                <x-filament-actions::modals />
            @endif
        </div>
        HTML;
    }

    public function editAction(): EditAction
    {
        return EditAuthenticatorAppAction::make()
            ->record($this->app);
    }

    public function deleteAction(): Action
    {
        return DeleteTotpAction::make('delete')
            ->record($this->app)
            ->after(function () {
                $this->dispatch(MfaEvent::AppDeleted->value, appId: $this->app->getKey());

                $this->app = null;
            });
    }
}
