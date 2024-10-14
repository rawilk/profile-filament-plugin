<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Filament\Pages\Profile\ProfileInfo;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    login($this->user = User::factory()->create());
});

it('renders', function () {
    livewire(ProfileInfo::class)
        ->assertSuccessful()
        ->assertSeeLivewire(Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo::class);
});
