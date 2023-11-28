<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Events\PendingUserEmails\EmailAddressReverted;
use Rawilk\ProfileFilament\Models\OldUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    Event::fake();

    config([
        'profile-filament.actions.store_old_user_email' => OldUserEmail::class,
        'profile-filament.pending_email_changes.revert_expiration' => DateInterval::createFromDateString('5 minutes'),
    ]);

    $this->user = User::factory()->create(['email' => 'changed@example.test']);
});

it('reverts an email change for a user', function () {
    Date::setTestNow('2023-01-01 10:00:00');

    $oldEmail = OldUserEmail::factory()->for($this->user)->create(['email' => 'old@example.test']);

    get($oldEmail->revert_url)
        ->assertRedirect('/admin/login');

    $this->travelTo(now()->addMinutes(5));

    expect(auth()->check())->toBeFalse()
        ->and($this->user->refresh())->email->toBe('old@example.test');

    $this->assertDatabaseMissing(OldUserEmail::class, [
        'email' => 'changed@example.test',
    ]);

    Event::assertDispatched(function (EmailAddressReverted $event) {
        expect($event->user)->toBe($this->user)
            ->and($event->revertedFrom)->toBe('changed@example.test')
            ->and($event->revertedTo)->toBe('old@example.test');

        return true;
    });
});

it('rejects invalid tokens', function () {
    $oldEmail = OldUserEmail::factory()->for($this->user)->create();
    $oldEmail->token = 'foo';

    get($oldEmail->revert_url)
        ->assertRedirect('/admin/login');

    Event::assertNotDispatched(EmailAddressReverted::class);

    expect($this->user->refresh())->email->toBe('changed@example.test');

    $this->assertDatabaseHas(OldUserEmail::class, [
        'id' => $oldEmail->id,
    ]);
});

it('blocks expired links', function () {
    Date::setTestNow('2023-01-01 10:00:00');
    $oldEmail = OldUserEmail::factory()->for($this->user)->create();
    $url = $oldEmail->revert_url;

    $this->travelTo(now()->addMinutes(5)->addSecond());

    get($url)
        ->assertForbidden();

    expect($this->user->refresh())->email->toBe('changed@example.test');
});
