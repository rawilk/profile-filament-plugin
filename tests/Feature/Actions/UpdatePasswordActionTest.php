<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Rawilk\ProfileFilament\Actions\UpdatePasswordAction;
use Rawilk\ProfileFilament\Events\UserPasswordWasUpdated;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

it('updates a users password', function () {
    Event::fake();
    $user = User::factory()->create(['password' => 'first_pass']);

    app(UpdatePasswordAction::class)($user, 'new_pass');

    Event::assertDispatched(UserPasswordWasUpdated::class);

    expect(Hash::check('new_pass', $user->refresh()->getAuthPassword()))->toBeTrue();
});
