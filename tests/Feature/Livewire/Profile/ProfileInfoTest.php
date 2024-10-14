<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Events\Profile\ProfileInformationUpdated;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->freezeTime();

    login($this->user = User::factory()->create(['name' => 'Dexter Morgan']));
});

it('displays profile info for a user', function () {
    livewire(ProfileInfo::class)
        ->assertSeeText('Dexter Morgan')
        ->assertSeeText(now()->format('M j, Y'))
        ->assertInfolistActionExists('profile-information', 'edit');
});

it('can edit a users profile information', function () {
    Event::fake();

    livewire(ProfileInfo::class)
        ->callInfolistAction('profile-information', 'edit', data: [
            'name' => 'New Name',
        ])
        ->assertHasNoInfolistActionErrors();

    Event::assertDispatched(ProfileInformationUpdated::class);

    expect($this->user->refresh())
        ->name->toBe('New Name');
});
