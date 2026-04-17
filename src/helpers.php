<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use DateTimeInterface;

function wrapDateInTimeTag(DateTimeInterface $date, string $format = 'F d, Y'): string
{
    // datetime format example: 2023-08-22T16:01:24Z

    return <<<HTML
    <time datetime="{$date->format('Y-m-d\TH:i:s\Z')}">{$date->format($format)}</time>
    HTML;
}
