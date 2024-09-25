<?php

declare(strict_types=1);

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationChallenged;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Http\Middleware\RequiresTwoFactorAuthentication;
use Rawilk\ProfileFilament\ProfileFilament;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\followingRedirects;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withoutExceptionHandling;

beforeEach(function () {
    Route::get('/login', fn () => 'login page')->name('login');

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

it('redirects to a mfa challenge', function () {
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
        ->assertSee('login page');

    Event::assertNotDispatched(TwoFactorAuthenticationChallenged::class);
});

test('user must use the TwoFactorAuthenticatable trait', function () {
    $userClass = new class extends Illuminate\Foundation\Auth\User
    {
        protected $table = 'users';

        protected $guarded = [];
    };

    $user = $userClass::create(['email' => 'foo@example.test', 'two_factor_enabled' => true]);

    withoutExceptionHandling()
        ->actingAs($user)
        ->get('/requires-mfa');
})->throws(RuntimeException::class);

it('ignores users that do not have mfa enabled', function () {
    $this->user->update(['two_factor_enabled' => false]);

    get('/requires-mfa')
        ->assertOk()
        ->assertSee('ok');
});

it('skips its check for routes named logout', function () {
    post('/foo')
        ->assertOk()
        ->assertSee('logout');

    expect(auth()->check())->toBeFalse();
});

test('custom conditions can be added to exclude the mfa check', function () {
    ProfileFilament::shouldCheckForMfaUsing(function ($request, $user) {
        return $user->id > 1;
    });

    get('/requires-mfa')->assertOk();

    $user = User::factory()->withMfa()->create();

    actingAs($user)
        ->get('/requires-mfa')
        ->assertRedirectToRoute('filament.admin.auth.mfa.challenge');
});

it('does not redirect if mfa is confirmed already', function () {
    Mfa::confirmUserSession($this->user);

    get('/requires-mfa')->assertOk();

    actingAs(User::factory()->withMfa()->create())
        ->get('/requires-mfa')
        ->assertRedirectToRoute('filament.admin.auth.mfa.challenge');
});
