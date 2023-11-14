<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use DateTimeInterface;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

function wrapDateInTimeTag(DateTimeInterface $date, string $format = 'M d, Y'): string
{
    // datetime format example: 2023-08-22T16:01:24Z

    return <<<HTML
    <time datetime="{$date->format('Y-m-d\TH:i:s\Z')}">{$date->format($format)}</time>
    HTML;
}

function renderMarkdown(?string $content, bool $inline = true): HtmlString
{
    $markdown = $inline
        ? Str::inlineMarkdown($content ?? '')
        : Str::markdown($content ?? '');

    return new HtmlString($markdown);
}
