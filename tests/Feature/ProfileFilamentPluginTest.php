<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Auth\Sudo\Webauthn\SudoWebauthnProvider;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Filament\Pages\Profile\ProfileInfo;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\be;

beforeEach(function () {
    $this->plugin = ProfileFilamentPlugin::make();
});

describe('tenancy', function () {
    it('defaults profile routes to global routes', function () {
        $panel = Filament::getPanel('global-profile-tenant');
        $route = Route::getRoutes()->getByName('filament.global-profile-tenant.profile.pages.user');

        expect(ProfileFilament::plugin($panel->getId())->isTenantAware())->toBeFalse()
            ->and($route)->not->toBeNull()
            ->and($route->uri())->toBe('global-profile-tenant/profile/user')
            ->and(ProfileInfo::getUrl(
                isAbsolute: false,
                panel: 'global-profile-tenant',
            ))->toBe('/global-profile-tenant/profile/user');
    });

    it('can opt into tenant-aware profile routes', function () {
        $panel = Filament::getPanel('tenant-aware-profile');
        $route = Route::getRoutes()->getByName('filament.tenant-aware-profile.profile.pages.user');

        expect(ProfileFilament::plugin($panel->getId())->isTenantAware())->toBeTrue()
            ->and($route)->not->toBeNull()
            ->and($route->uri())->toBe('tenant-aware-profile/{tenant}/profile/user');
    });
});

describe('sudo mode', function () {
    it('defaults enabled sudo providers to the password providers if none are provided', function () {
        $this->plugin->sudoMode(providers: null);

        $providers = $this->plugin->getSudoChallengeProviders();

        expect($providers)->not->toBeEmpty()
            ->toHaveKey('password');
    });

    it('can enable sudo mode', function () {
        $this->plugin->sudoMode(providers: [
            SudoWebauthnProvider::make(),
        ]);

        expect($this->plugin->hasSudoMode())->toBeTrue()
            ->and($this->plugin->getSudoChallengeProviders())->toHaveKey('webauthn')->toHaveCount(1);
    });

    it('can disable sudo mode', function () {
        $this->plugin->sudoMode(providers: false);

        expect($this->plugin->hasSudoMode())->toBeFalse();
    });

    it('can conditionally disable sudo mode checks with a callback', function () {
        be($user = User::factory()->create());

        $this->plugin->sudoMode(providers: null);

        expect($this->plugin->hasSudoMode())->toBeTrue();

        $this->plugin->onlyChallengeSudoWhen(fn (): bool => auth()->user()->isNot($user));

        expect($this->plugin->hasSudoMode())->toBeFalse();

        be(User::factory()->create());

        expect($this->plugin->hasSudoMode())->toBeTrue();
    });
});
