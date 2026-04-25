<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Infolists\Components;

use Filament\Actions\Action;
use Filament\Schemas\Components\Text;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;

class PendingEmailChangeText extends Text
{
    protected ?Model $pendingEmail = null;

    protected ?Action $cancelPendingEmailChangeAction = null;

    protected ?Action $resendPendingEmailAction = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->content(
            fn () => new HtmlString(Blade::render(<<<'HTML'
            <div class="px-4 py-3 rounded-md border border-gray-300 dark:border-gray-600">
                <div class="flex gap-x-2 items-start">
                    <div class="shrink-0">
                        <x-filament::icon
                            :alias="$iconAlias"
                            icon="heroicon-o-information-circle"
                            class="h-5 w-5 text-primary-500 dark:text-primary-400"
                        />
                    </div>

                    <div class="flex-1">
                        <div class="text-sm font-bold">{{ __('profile-filament::pages/settings.email.pending_heading') }}</div>

                        <div class="mt-1 text-sm">
                            {{
                                str(__('profile-filament::pages/settings.email.pending_description', [
                                    'email' => e($pendingEmail?->email)
                                ]))
                                    ->inlineMarkdown()
                                    ->toHtmlString()
                            }}
                        </div>

                        @php
                            $hasResend = filled($resendAction);
                            $hasCancel = filled($cancelAction);
                        @endphp
                        @if ($hasResend || $hasCancel)
                            <div class="mt-3 flex items-center gap-x-2">
                                @if ($hasResend)
                                    {{ $resendAction }}
                                @endif
                                <span
                                    @class([
                                        'hidden rounded-full h-1 w-1 bg-gray-600',
                                        'inline-block' => $hasResend && $hasCancel,
                                    ])
                                    aria-hidden="true"
                                ></span>
                                @if ($hasCancel)
                                    {{ $cancelAction }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            HTML, [
                'icon' => ProfileFilamentIcon::PendingEmailInfo->resolve(),
                'pendingEmail' => $this->pendingEmail,
                'resendAction' => $this->resendPendingEmailAction,
                'cancelAction' => $this->cancelPendingEmailChangeAction,
            ]))
        );
    }

    public function withCancelAction(?Action $action = null): static
    {
        $this->cancelPendingEmailChangeAction = $action;

        return $this;
    }

    public function withResendAction(?Action $action = null): static
    {
        $this->resendPendingEmailAction = $action;

        return $this;
    }

    public function usingEmail(?Model $email): static
    {
        $this->pendingEmail = $email;

        return $this;
    }
}
