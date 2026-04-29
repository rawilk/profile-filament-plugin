<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

it('knows if it is expired', function () {
    config()->set('auth.verification.expire', 120);

    $this->freezeSecond();

    $record = PendingUserEmail::factory()->create();

    $this->travel(120)->minutes();

    expect($record->isExpired())->toBeFalse();

    $this->travel(1)->second();

    expect($record->isExpired())->toBeTrue();
});

it('can scope queries to a user', function () {
    [$user1, $user2] = User::factory()->count(2)->create();

    $records = PendingUserEmail::factory()->for($user1)->count(2)->create();
    PendingUserEmail::factory()->for($user2)->count(2)->create();

    $results = PendingUserEmail::forUser($user1)->get();

    expect($results)->toHaveCount(2)
        ->modelsMatchExactly($records);
});
