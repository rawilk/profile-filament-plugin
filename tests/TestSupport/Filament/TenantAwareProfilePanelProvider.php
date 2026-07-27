<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\TestSupport\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\Tenant;

class TenantAwareProfilePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant-aware-profile')
            ->path('tenant-aware-profile')
            ->tenant(Tenant::class)
            ->plugin(
                ProfileFilamentPlugin::make()
                    ->tenantAware()
            );
    }
}
