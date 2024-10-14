<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Filament\Pages\Profile\Sessions;
use Rawilk\ProfileFilament\Livewire\Sessions\SessionManager;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    login($this->user = User::factory()->create());
});

it('renders', function () {
    livewire(Sessions::class)
        ->assertSuccessful()
        ->assertSeeLivewire(SessionManager::class);
});
