<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile;

use Illuminate\Contracts\Support\Htmlable;
use Rawilk\ProfileFilament\Concerns;

class ProfileInfo extends ProfilePage
{
    use Concerns\HasPanelPageRoutes;

    public static function getNavigationLabel(): string
    {
        return __('profile-filament::pages/profile.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::pages/profile.title');
    }
}
