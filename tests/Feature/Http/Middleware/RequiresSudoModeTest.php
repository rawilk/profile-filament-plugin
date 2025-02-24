<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Http\Middleware\RequiresSudoMode;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    Route::get('/requires-sudo', fn () => 'ok')->middleware(RequiresSudoMode::class);

    $this->freezeSecond();

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

it('extends sudo mode if it is already active', function () {
    Sudo::activate();

    $this->travelTo(now()->addHours(2)->subSecond());

    get('/requires-sudo')
        ->assertSee('ok');

    Event::assertNotDispatched(SudoModeChallenged::class);

    expect(now())->toBeSudoSessionValue();
});

it('redirects if sudo is expired', function () {
    Sudo::activate();

    $this->travel(2)->hours();

    get('/requires-sudo')
        ->assertRedirectToRoute('filament.admin.auth.sudo-challenge');

    Event::assertDispatched(SudoModeChallenged::class);

    expect(session()->has(SudoSession::ConfirmedAt->value))->toBeFalse();
});

it('does nothing if sudo mode is disabled', function () {
    disableSudoMode();

    get('/requires-sudo')
        ->assertSuccessful()
        ->assertSeeText('ok');

    Event::assertNotDispatched(SudoModeChallenged::class);

    expect(session()->has(SudoSession::ConfirmedAt->value))->toBeFalse();
});
