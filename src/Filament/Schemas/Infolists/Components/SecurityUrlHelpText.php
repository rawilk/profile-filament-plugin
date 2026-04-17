<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Infolists\Components;

use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;

class SecurityUrlHelpText extends Text
{
    protected ?string $url = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->content(
            fn () => new HtmlString(Blade::render(<<<'HTML'
            <div class="flex items-center gap-x-2 text-xs [&_a]:text-primary-600 dark:[&_a]:text-primary-400 [&_a:hover]:underline">
                <div>
                    <x-filament::icon
                        :alias="$iconAlias"
                        :icon="$icon"
                        class="h-4 w-4"
                    />
                </div>

                <span>
                    {{
                        str(__('profile-filament::pages/settings.account_security_link', [
                            'url' => $url,
                        ]))
                            ->inlineMarkdown()
                            ->toHtmlString()
                    }}
                </span>
            </div>
            HTML, [
                'iconAlias' => ProfileFilamentIcon::Help->value,
                'icon' => Heroicon::OutlinedQuestionMarkCircle,
                'url' => $this->url,
            ]))
        );
    }

    public function usingUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
