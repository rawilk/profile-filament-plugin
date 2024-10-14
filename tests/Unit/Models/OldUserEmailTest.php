<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Events\PendingUserEmails\EmailAddressReverted;
use Rawilk\ProfileFilament\Exceptions\PendingUserEmails\InvalidRevertLinkException;
use Rawilk\ProfileFilament\Models\OldUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\VerifyEmailUser;

beforeEach(function () {
    config([
        'profile-filament.pending_email_changes.revert_expiration' => DateInterval::createFromDateString('5 minutes'),
    ]);
});

test('query can be scoped to a user', function () {
    $users = User::factory()->count(2)->create();

    $records = OldUserEmail::factory()
        ->sequence(
            ['user_id' => $users->first()->id, 'user_type' => User::class],
            ['user_id' => $users->last()->id, 'user_type' => User::class],
        )
        ->count(5)
        ->create();

    $results = OldUserEmail::forUser($users->first())->get();

    expect($results)->toHaveCount(3)
        ->modelsMatchExactly($records->filter(fn (OldUserEmail $record) => $record->user()->is($users->first())));
});

it('knows if it has expired', function () {
    $this->freezeSecond();

    $record = OldUserEmail::factory()->for(User::factory())->create();

    $this->travel(5)->minutes();
    expect($record->isExpired())->toBeFalse();

    $this->travel(1)->second();
    expect($record->isExpired())->toBeTrue();
});

it('has a revert url attribute', function () {
    $this->freezeSecond();

    $record = OldUserEmail::factory()->for(User::factory())->create(['token' => 'my_token']);

    expect($record->revert_url)
        ->toContain('/my_token')
        ->toContain('expires=' . now()->addMinutes(5)->unix())
        ->toContain('signature');
});

it('can activate itself', function () {
    Event::fake();

    $user = User::factory()->create(['email' => 'email@example.test']);
    $record = OldUserEmail::factory()->for($user)->create(['email' => 'old@example.test']);

    $record->activate();

    expect($user->refresh())->email->toBe('old@example.test');

    Event::assertDispatched(function (EmailAddressReverted $event) use ($user) {
        expect($event->user)->toBe($user)
            ->and($event->revertedFrom)->toBe('email@example.test')
            ->and($event->revertedTo)->toBe('old@example.test');

        return true;
    });

    $this->assertModelMissing($record);
});

test('expired tokens cannot be activated', function () {
    $this->freezeSecond();

    $record = OldUserEmail::factory()->for(User::factory())->create();

    $this->travelTo(now()->addMinutes(5)->addSecond());

    $record->activate();
})->throws(InvalidRevertLinkException::class);

it('will not activate if the email is assigned to another user', function () {
    User::factory()->create(['email' => 'taken@email.test']);

    $record = OldUserEmail::factory()->for(User::factory())->create(['email' => 'taken@email.test']);

    $record->activate();
})->throws(InvalidRevertLinkException::class);

it('re-verifies an email for a user if they are an instance of MustVerifyEmail', function () {
    $this->freezeSecond();

    $user = VerifyEmailUser::factory()->create();
    $record = OldUserEmail::factory()->for($user, 'user')->create();

    $record->activate();

    expect($user->refresh())
        ->email_verified_at->toBe(now());
});
