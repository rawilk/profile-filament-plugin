<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\DeleteAccountAction;
use Rawilk\ProfileFilament\Events\UserDeletedAccount;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

it('deletes a user account', function () {
    Event::fake();

    $user = User::factory()->create();

    app(DeleteAccountAction::class)($user);

    Event::assertDispatched(function (UserDeletedAccount $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});
