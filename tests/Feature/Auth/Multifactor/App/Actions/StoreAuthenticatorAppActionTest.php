<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorEnabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Actions\StoreAuthenticatorAppAction;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasCreated;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    config()->set('profile-filament.actions.mark_multifactor_enabled', MarkMultiFactorEnabledAction::class);
    config()->set('profile-filament.models.authenticator_app', AuthenticatorApp::class);

    Event::fake();

    $this->action = StoreAuthenticatorAppAction::class;
});

it('stores an authenticator app', function () {
    $user = User::factory()->create();

    app($this->action)(
        $user,
        'My app',
        $secret = Str::random(),
    );

    assertDatabaseHas(AuthenticatorApp::class, [
        'user_id' => $user->getKey(),
        'name' => 'My app',
    ]);

    $record = $user->authenticatorApps()->first();

    expect($record)->secret->toBe($secret);
});

it('calls the MarkMultiFactorEnabledAction', function () {
    $user = User::factory()->create();

    $this->mock(MarkMultiFactorEnabledAction::class)
        ->shouldReceive('__invoke')
        ->with($user)
        ->once();

    app($this->action)(
        $user,
        'My app',
        Str::random(),
    );
});

it('fires a AuthenticatorAppWasCreated event', function () {
    $user = User::factory()->create();

    app($this->action)(
        $user,
        'My app',
        Str::random(),
    );

    Event::assertDispatched(AuthenticatorAppWasCreated::class, function (AuthenticatorAppWasCreated $event) {
        expect($event->authenticatorApp->name)->toBe('My app');

        return true;
    });
});
