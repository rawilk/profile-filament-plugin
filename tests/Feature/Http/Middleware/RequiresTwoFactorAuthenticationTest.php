<?php

declare(strict_types=1);

use Filament\Http\Middleware\Authenticate;
use Filament\Pages\Auth\Login;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationChallenged;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Http\Middleware\RequiresTwoFactorAuthentication;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\followingRedirects;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withoutExceptionHandling;

beforeEach(function () {
    Route::post('/foo', function () {
        auth()->logout();

        return 'logout';
    })->middleware([Authenticate::class, RequiresTwoFactorAuthentication::class])->name('logout');

    Route::get('/requires-mfa', fn () => 'ok')->middleware([
        Authenticate::class,
        RequiresTwoFactorAuthentication::class,
    ]);

    Event::fake();

    login($this->user = User::factory()->withMfa()->create());
});

afterEach(function () {
    ProfileFilament::shouldCheckForMfaUsing(null);
});

it('redirects to the mfa challenge', function () {
    get('/requires-mfa')
        ->assertRedirectToRoute('filament.admin.auth.mfa.challenge');

    Event::assertDispatched(TwoFactorAuthenticationChallenged::class);

    expect(session()->get('url.intended'))->toBe('https://acme.test/requires-mfa');
});

it('does nothing for guests', function () {
    auth()->logout();

    followingRedirects()
        ->get('/requires-mfa')
        ->assertOk()
        ->assertSeeLivewire(Login::class);

    Event::assertNotDispatched(TwoFactorAuthenticationChallenged::class);
});

test('user must use the TwoFactorAuthenticatable trait', function () {
    $model = new class extends Illuminate\Foundation\Auth\User
    {
        protected $table = 'users';

        protected $guarded = [];
    };

    $user = $model::create(['email' => 'foo@example.test', 'two_factor_enabled' => true]);

    withoutExceptionHandling()
        ->actingAs($user)
        ->get('/requires-mfa');
})->throws(RuntimeException::class);

it('ignores users that do not have mfa enabled', function () {
    $user = User::factory()->withoutMfa()->create();

    actingAs($user)
        ->get('/requires-mfa')
        ->assertOk()
        ->assertSeeText('ok');
});

it('skips its check for routes named logout', function () {
    post('/foo')
        ->assertOk()
        ->assertSeeText('logout');

    Event::assertNotDispatched(TwoFactorAuthenticationChallenged::class);

    $this->assertGuest();
});

test('custom conditions can be used to exclude the mfa check', function () {
    ProfileFilament::shouldCheckForMfaUsing(function ($request, $user) {
        // Only the user that has an id of 9999 should be required to present mfa in this case,
        // even if the other users have it enabled on their account.
        return $user->getKey() === 9999;
    });

    get('/requires-mfa')->assertSeeText('ok');

    Event::assertNotDispatched(TwoFactorAuthenticationChallenged::class);

    $user = User::factory()->withMfa()->create(['id' => 9999]);

    actingAs($user)
        ->get('/requires-mfa')
        ->assertRedirectToRoute('filament.admin.auth.mfa.challenge');

    Event::assertDispatched(TwoFactorAuthenticationChallenged::class);
});

it('does not redirect if mfa is already confirmed', function () {
    Mfa::confirmUserSession($this->user);

    get('/requires-mfa')->assertSeeText('ok');

    Event::assertNotDispatched(TwoFactorAuthenticationChallenged::class);

    actingAs(User::factory()->withMfa()->create())
        ->get('/requires-mfa')
        ->assertRedirectToRoute('filament.admin.auth.mfa.challenge');

    Event::assertDispatched(TwoFactorAuthenticationChallenged::class);
});
