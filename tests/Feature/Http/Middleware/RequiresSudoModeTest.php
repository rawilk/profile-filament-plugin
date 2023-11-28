<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Http\Middleware\RequiresSudoMode;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    Route::get('/requires-sudo', fn () => 'ok')->middleware(RequiresSudoMode::class);

    Date::setTestNow('2023-01-01 10:00:00');

    Event::fake();

    login(User::factory()->create());

    config([
        'profile-filament.sudo.expires' => DateInterval::createFromDateString('2 hours'),
    ]);
});

it('redirects to a sudo mode challenge', function () {
    get('/requires-sudo')
        ->assertRedirectToRoute('filament.admin.auth.sudo-challenge');

    Event::assertDispatched(SudoModeChallenged::class);

    expect(session()->get('url.intended'))->toBe('https://acme.test/requires-sudo');
});

it('extends sudo mode if it is active', function () {
    Sudo::activate();

    $this->travelTo(now()->addHours(2)->subSecond());

    get('/requires-sudo')
        ->assertSuccessful();

    Event::assertNotDispatched(SudoModeChallenged::class);

    expect('2023-01-01 11:59:59')->toBeSudoSessionValue();
});

it('redirects if sudo is expired', function () {
    Sudo::activate();

    $this->travelTo(now()->addHours(2));

    get('/requires-sudo')
        ->assertRedirectToRoute('filament.admin.auth.sudo-challenge');

    Event::assertDispatched(SudoModeChallenged::class);

    expect(session()->has(SudoSession::ConfirmedAt->value))->toBeFalse();
});

it('does nothing if sudo mode is disabled', function () {
    disableSudoMode();

    get('/requires-sudo')
        ->assertSuccessful();

    Event::assertNotDispatched(SudoModeChallenged::class);

    expect(session()->has(SudoSession::ConfirmedAt->value))->toBeFalse();
});
