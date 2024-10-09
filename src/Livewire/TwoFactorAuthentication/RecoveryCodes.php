<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication;

use Filament\Actions\Action;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Contracts\TwoFactor\GenerateNewRecoveryCodesAction;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\GenerateNewRecoveryCodesAction as GenerateCodesFilamentAction;
use Rawilk\ProfileFilament\Livewire\Concerns\CopiesRecoveryCodes;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;

/**
 * @property-read array $recoveryCodes
 */
class RecoveryCodes extends ProfileComponent
{
    use CopiesRecoveryCodes;

    #[Locked]
    public bool $regenerated = false;

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{-- list --}}
            <div
                id="recovery-codes-list"
                class="text-sm sm:text-base my-6 rounded-md border border-gray-300 dark:border-gray-500 text-gray-950 sm:w-3/4 dark:text-white pb-8"
            >
                <div class="px-4">
                    {{-- intro --}}
                    <div class="pt-4 sm:pl-4">
                        <h3 class="text-lg font-semibold">{{ __('profile-filament::pages/security.mfa.recovery_codes.current_codes_title') }}</h3>

                        <div
                            class="[&_a]:text-custom-600 [&_a]:fi-link [&_a:focus]:underline [&_a:hover]:underline dark:[&_a]:text-custom-400"
                            @style([
                                \Filament\Support\get_color_css_variables('primary', [300, 400, 500, 600])
                            ])
                        >
                            <p class="text-xs text-gray-500 dark:text-gray-300">
                                {{
                                    str(__('profile-filament::pages/security.mfa.recovery_codes.recommendation', [
                                        '1password' => 'https://1password.com/',
                                        'authy' => 'https://authy.com/',
                                        'keeper' => 'https://www.keepersecurity.com/'
                                    ]))
                                        ->inlineMarkdown()
                                        ->toHtmlString()
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- warning --}}
                <div class="mt-4">
                    <x-profile-filament::alert
                        :color="$regenerated ? 'danger' : 'primary'"
                        icon="heroicon-o-exclamation-triangle"
                        alias="mfa::recovery-codes-notice"
                        class="!rounded-none !border-x-0 text-pretty"
                    >
                        {{
                            str(
                                $regenerated
                                    ? __('profile-filament::pages/security.mfa.recovery_codes.regenerated_warning')
                                    : __('profile-filament::pages/security.mfa.recovery_codes.warning')
                            )
                                ->markdown()
                                ->toHtmlString()
                        }}
                    </x-profile-filament::alert>
                </div>

                {{-- code grid --}}
                <div class="px-4">
                    <ul class="grid sm:grid-cols-2 sm:gap-x-6 font-mono list-disc list-inside mt-4"
                        role="list"
                    >
                        @foreach ($this->recoveryCodes as $code)
                            <li class="sm:text-center">{{ $code }}</li>
                        @endforeach
                    </ul>

                    {{-- code actions --}}
                    <div class="mt-6 sm:pl-4 flex gap-x-4">
                        {{ $this->downloadAction }}
                        {{ $this->printAction }}
                        {{ $this->copyAction }}
                    </div>
                </div>
            </div>

            {{-- code regeneration --}}
            <div class="mt-2">
                <p class="text-sm font-bold">{{ __('profile-filament::pages/security.mfa.recovery_codes.actions.generate.heading') }}</p>
                <p class="text-xs mt-1 text-gray-500 dark:text-gray-300">
                    {{ str(__('profile-filament::pages/security.mfa.recovery_codes.actions.generate.description'))->inlineMarkdown()->toHtmlString() }}
                </p>

                <div class="mt-4">
                    {{ $this->generateAction }}
                </div>
            </div>

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    public function generateAction(): Action
    {
        return GenerateCodesFilamentAction::make('generate')
            ->using(function (GenerateNewRecoveryCodesAction $generator): bool {
                $generator(filament()->auth()->user());

                $this->regenerated = true;

                return true;
            });
    }
}
