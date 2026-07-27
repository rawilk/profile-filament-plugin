<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\TestSupport\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\Tenant;

class GlobalProfileTenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('global-profile-tenant')
            ->path('global-profile-tenant')
            ->tenant(Tenant::class)
            ->plugin(ProfileFilamentPlugin::make());
    }
}
