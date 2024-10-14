<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Filament\Pages\Profile\Settings;
use Rawilk\ProfileFilament\Livewire\DeleteAccount;
use Rawilk\ProfileFilament\Livewire\Emails\UserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    login($this->user = User::factory()->create());
});

it('renders', function () {
    livewire(Settings::class)
        ->assertSuccessful()
        ->assertSeeLivewire(UserEmail::class)
        ->assertSeeLivewire(DeleteAccount::class);
});
