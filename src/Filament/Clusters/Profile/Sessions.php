<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Clusters\Profile;

use Illuminate\Contracts\Support\Htmlable;

class Sessions extends ProfilePage
{
    public static function getNavigationLabel(): string
    {
        return __('profile-filament::pages/sessions.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::pages/sessions.title');
    }
}
