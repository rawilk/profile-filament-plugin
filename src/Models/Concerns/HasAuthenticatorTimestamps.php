<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Facades\ProfileFilament;

use function Rawilk\ProfileFilament\wrapDateInTimeTag;

/**
 * @property-read HtmlString $last_used
 * @property-read HtmlString $registered_at
 */
trait HasAuthenticatorTimestamps
{
    public function lastUsed(): Attribute
    {
        return Attribute::make(
            get: function () {
                $timezone = ProfileFilament::userTimezone();
                $date = $this->last_used_at?->tz($timezone);
                $now = now()->tz($timezone);

                if (blank($date)) {
                    return __('profile-filament::messages.multi-factor-device.never-used');
                }

                // Show a relative time if used within the last week.
                if ($date->isAfter($now->subWeek())) {
                    $diff = $date->diffForHumans(options: Carbon::JUST_NOW);

                    $wrappedDate = <<<HTML
                    <time datetime="{$date->format('Y-m-d\TH:i:s\Z')}">{$diff}</time>
                    HTML;

                    return str(__('profile-filament::messages.multi-factor-device.last-used-relative', ['date' => $wrappedDate]))
                        ->inlineMarkdown()
                        ->toHtmlString();
                }

                return str(
                    __('profile-filament::messages.multi-factor-device.last-used', [
                        'date' => wrapDateInTimeTag($date, 'F d, Y g:i a'),
                    ])
                )->inlineMarkdown()->toHtmlString();
            },
        )->shouldCache();
    }

    protected function registeredAt(): Attribute
    {
        return Attribute::make(
            get: function () {
                $date = $this->created_at->tz(ProfileFilament::userTimezone());

                $translation = __('profile-filament::messages.multi-factor-device.created-at', ['date' => wrapDateInTimeTag($date)]);

                return str($translation)->inlineMarkdown()->toHtmlString();
            },
        )->shouldCache();
    }
}
