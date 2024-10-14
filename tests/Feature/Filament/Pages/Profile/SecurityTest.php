<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Filament\Pages\Profile\Security;
use Rawilk\ProfileFilament\Livewire\MfaOverview;
use Rawilk\ProfileFilament\Livewire\PasskeyManager;
use Rawilk\ProfileFilament\Livewire\Sudo\SudoChallengeForm;
use Rawilk\ProfileFilament\Livewire\UpdatePassword;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    login($this->user = User::factory()->create());
});

it('renders', function () {
    livewire(Security::class)
        ->assertSuccessful()
        ->assertSeeLivewire(UpdatePassword::class)
        ->assertSeeLivewire(MfaOverview::class)
        ->assertSeeLivewire(PasskeyManager::class)
        ->assertSeeLivewire(SudoChallengeForm::class);
});
