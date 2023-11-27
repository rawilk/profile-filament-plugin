<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Events\Profile\ProfileInformationUpdated;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Date::setTestNow('2023-01-01 10:00:00');

    login($this->user = User::factory()->create(['name' => 'John Smith']));
});

it('displays profile information for a user', function () {
    livewire(ProfileInfo::class)
        ->assertSeeText('John Smith')
        ->assertSeeText('Jan 1, 2023')
        ->assertActionExists('edit');
});

it('can edit the profile info for a user', function () {
    Event::fake();

    livewire(ProfileInfo::class)
        ->mountAction('edit')
        ->setActionData([
            'name' => 'New Name',
        ])
        ->callAction('edit')
        ->assertNotified();

    Event::assertDispatched(ProfileInformationUpdated::class);

    expect($this->user->refresh())
        ->name->toBe('New Name');
});
