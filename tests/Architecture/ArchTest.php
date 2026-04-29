<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Filament\Actions\Sessions\Concerns\ManagesSessions;
use Rawilk\ProfileFilament\Filament\Pages\Profile\PageConfiguration\ProfilePageConfiguration;
use Rawilk\ProfileFilament\Filament\Pages\Profile\ProfilePage;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\ProfileFilamentPluginServiceProvider;
use Rawilk\ProfileFilament\Providers\AuthServiceProvider;

arch()->preset()->php();
arch()->preset()->security()->ignoring([
    ManagesSessions::class, // Needs unserialize
]);

arch('custom strict')
    ->expect('Rawilk\ProfileFilament')
    ->toUseStrictTypes()
    ->toUseStrictEquality()
    ->classes()->not->toBeAbstract()->ignoring([
        ProfileComponent::class,
        ProfilePageConfiguration::class,
        ProfilePage::class,
        ProfileFilamentEvent::class,
    ])
    ->classes()->not->toBeFinal()->ignoring([
        AuthServiceProvider::class,
        Rawilk\ProfileFilament\Support\Config::class,
        ProfileFilamentPluginServiceProvider::class,
    ]);

arch('strict preset globals')
    ->expect(['sleep', 'usleep'])
    ->not->toBeUsed();

arch('traits')
    ->expect([
        'Rawilk\ProfileFilament\Concerns',
        'Rawilk\ProfileFilament\*\Concerns',
        'Rawilk\ProfileFilament\*\*\Concerns',
        'Rawilk\ProfileFilament\*\*\*\Concerns',
    ])
    ->toBeTraits();

arch('interfaces')
    ->expect([
        'Rawilk\ProfileFilament\Contracts',
        'Rawilk\ProfileFilament\*\*\Contracts',
        'Rawilk\ProfileFilament\*\*\*\Contracts',
    ])
    ->toBeInterfaces();

arch('enums')
    ->expect([
        'Rawilk\ProfileFilament\Enums',
        'Rawilk\ProfileFilament\*\*\Enums',
        'Rawilk\ProfileFilament\*\*\*\Enums',
    ])
    ->toBeEnums()
    ->not->toHaveSuffix('Enum');

arch('dto')
    ->expect([
        'Rawilk\ProfileFilament\Dto',
        'Rawilk\ProfileFilament\*\*\Dto',
    ])
    ->classes()
    ->toExtendNothing()
    ->not->toHaveSuffix('Dto');
