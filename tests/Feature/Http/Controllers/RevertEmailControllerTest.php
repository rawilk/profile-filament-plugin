<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
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
    $record = OldUserEmail::factory()->for($this->user)->create(['email' => 'old@example.test']);

    get($record->revert_url)
        ->assertRedirect('/admin/login');

    $this->assertGuest();

    expect($this->user->refresh())->email->toBe('old@example.test');

    $this->assertModelMissing($record);

    Event::assertDispatched(function (EmailAddressReverted $event) {
        expect($event->user)->toBe($this->user)
            ->and($event->revertedFrom)->toBe('changed@example.test')
            ->and($event->revertedTo)->toBe('old@example.test');

        return true;
    });
});

it('rejects invalid tokens', function () {
    $record = OldUserEmail::factory()->for($this->user)->create();
    $record->token = 'invalid';

    get($record->revert_url)
        ->assertRedirect('/admin/login');

    Event::assertNotDispatched(EmailAddressReverted::class);

    expect($this->user->refresh())->email->toBe('changed@example.test');

    $this->assertModelExists($record);
});

it('rejects expired links', function () {
    $this->freezeSecond();

    $record = OldUserEmail::factory()->for($this->user)->create();

    $url = URL::signedRoute(
        'filament.admin.pending_email.revert',
        [
            'token' => $record->token,
        ]
    );

    $this->travelTo(now()->addMinutes(5)->addSecond());

    get($url)
        ->assertRedirect('/admin/login');

    Event::assertNotDispatched(EmailAddressReverted::class);

    expect($this->user->refresh())->email->toBe('changed@example.test');
});
