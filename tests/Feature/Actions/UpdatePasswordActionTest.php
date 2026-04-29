<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\UpdatePasswordAction;
use Rawilk\ProfileFilament\Events\UserPasswordWasUpdated;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

it('updates the password for a user', function () {
    Event::fake();

    $user = User::factory()->create();

    app(UpdatePasswordAction::class)($user, newPassword: 'new_pass');

    Event::assertDispatched(UserPasswordWasUpdated::class);

    expect('new_pass')->toBePasswordFor($user);
});
