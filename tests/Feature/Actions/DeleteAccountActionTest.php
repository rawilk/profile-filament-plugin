<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\DeleteAccountAction;
use Rawilk\ProfileFilament\Events\UserDeletedAccount;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\assertModelMissing;

it('deletes a user account', function () {
    Event::fake();

    $user = User::factory()->create();

    app(DeleteAccountAction::class)($user);

    Event::assertDispatched(function (UserDeletedAccount $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });

    assertModelMissing($user);
});
