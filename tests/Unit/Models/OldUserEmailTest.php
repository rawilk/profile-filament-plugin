<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Date;
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
    User::factory()->count(2)->create();
    OldUserEmail::factory()
        ->state(new Sequence(
            ['user_id' => 1, 'user_type' => User::class],
            ['user_id' => 2, 'user_type' => User::class],
        ))
        ->count(5)
        ->create();

    $models = OldUserEmail::forUser($user = User::first())->get();

    expect($models)->toHaveCount(3)
        ->first()->user->toBe($user);
});

it('knows if it is expired', function () {
    Date::setTestNow('2023-01-01 10:00:00');

    $model = OldUserEmail::factory()->for(User::factory())->create();

    $this->travelTo(now()->addMinutes(5)->subSecond());
    expect($model->isExpired())->toBeFalse();

    $this->travelTo(now()->addSeconds(2));
    expect($model->isExpired())->toBeTrue();
});

it('has a revert url attribute', function () {
    $model = OldUserEmail::factory()->for(User::factory())->create(['token' => 'my_token']);

    expect($model->revert_url)
        ->toContain('/my_token')
        ->toContain('expires')
        ->toContain('signature');
});

it('can activate itself', function () {
    Event::fake();
    $user = User::factory()->create(['email' => 'email@example.test']);
    $oldEmail = OldUserEmail::factory()->for($user)->create(['email' => 'old@example.test']);

    $oldEmail->activate();

    expect($user->refresh())->email->toBe('old@example.test');

    Event::assertDispatched(function (EmailAddressReverted $event) use ($user) {
        expect($event->user)->toBe($user)
            ->and($event->revertedFrom)->toBe('email@example.test')
            ->and($event->revertedTo)->toBe('old@example.test');

        return true;
    });

    $this->assertDatabaseMissing(OldUserEmail::class, [
        'email' => 'old@example.test',
    ]);
});

test('expired tokens can not be activated', function () {
    Date::setTestNow('2023-01-01 10:00:00');
    $oldEmail = OldUserEmail::factory()->for(User::factory())->create();

    $this->travelTo(now()->addMinutes(5)->addSecond());

    $oldEmail->activate();
})->throws(InvalidRevertLinkException::class);

it('will not activate itself if the email is already taken by another user', function () {
    User::factory()->create(['email' => 'taken@example.test']);
    $oldEmail = OldUserEmail::factory()->for(User::factory())->create(['email' => 'taken@example.test']);

    $oldEmail->activate();
})->throws(InvalidRevertLinkException::class);

it('re-verifies an email for a user if they are an instance of MustVerifyEmail', function () {
    Date::setTestNow('2023-01-01 10:00:00');

    $user = VerifyEmailUser::factory()->create();
    $oldEmail = OldUserEmail::factory()->for($user, 'user')->create();

    $oldEmail->activate();

    expect($user->refresh())->email_verified_at->toDateTimeString()->toBe('2023-01-01 10:00:00');
});
