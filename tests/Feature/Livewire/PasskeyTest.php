<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\Passkeys\DeletePasskeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyDeleted;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyUpdated;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasDisabled;
use Rawilk\ProfileFilament\Livewire\Passkey;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    disableSudoMode();

    config([
        'profile-filament.actions.delete_passkey' => DeletePasskeyAction::class,
    ]);

    login($this->user = User::factory()->withMfa()->create());
    $this->passkey = WebauthnKey::factory()->passkey()->for($this->user)->create(['name' => 'my passkey']);
});

it('can be rendered', function () {
    livewire(Passkey::class, ['passkey' => $this->passkey])
        ->assertSuccessful()
        ->assertActionExists('edit')
        ->assertActionExists('delete');
});

it('can edit a passkey name', function () {
    livewire(Passkey::class, ['passkey' => $this->passkey])
        ->mountAction('edit')
        ->callAction('edit', [
            'name' => 'updated name',
        ])
        ->assertSuccessful()
        ->assertSeeText('updated name');

    Event::assertDispatched(PasskeyUpdated::class);

    expect($this->passkey->refresh())->name->toBe('updated name');
});

test('name is required', function () {
    livewire(Passkey::class, ['passkey' => $this->passkey])
        ->mountAction('edit')
        ->callAction('edit', [
            'name' => '',
        ])
        ->assertHasActionErrors([
            'name' => 'required',
        ]);

    Event::assertNotDispatched(PasskeyUpdated::class);

    expect($this->passkey->refresh())->name->toBe('my passkey');
});

it('requires a unique key name', function () {
    WebauthnKey::factory()->notPasskey()->for($this->user)->create(['name' => 'taken name']);

    livewire(Passkey::class, ['passkey' => $this->passkey])
        ->mountAction('edit')
        ->callAction('edit', [
            'name' => 'taken name',
        ])
        ->assertHasActionErrors([
            'name' => 'unique',
        ]);

    Event::assertNotDispatched(PasskeyUpdated::class);

    expect($this->passkey->refresh())->name->toBe('my passkey');
});

it('requires authorization to edit the name', function () {
    login(User::factory()->withMfa()->create());

    try {
        livewire(Passkey::class, ['passkey' => $this->passkey])
            ->mountAction('edit')
            ->callAction('edit', [
                'name' => 'updated name',
            ]);
    } catch (ErrorException) {
    }

    Event::assertNotDispatched(PasskeyUpdated::class);

    expect($this->passkey->refresh())->name->toBe('my passkey');
});

it('can delete a passkey', function () {
    livewire(Passkey::class, ['passkey' => $this->passkey])
        ->callAction('delete')
        ->assertNotified()
        ->assertDispatched(MfaEvent::PasskeyDeleted->value, id: $this->passkey->id)
        ->assertSet('passkey', null);

    Event::assertDispatched(PasskeyDeleted::class);
    Event::assertDispatched(TwoFactorAuthenticationWasDisabled::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'id' => $this->passkey->id,
    ]);
});

it('requires sudo mode to delete a passkey', function () {
    enableSudoMode();

    livewire(Passkey::class, ['passkey' => $this->passkey])
        ->call('mountAction', 'delete')
        ->assertActionMounted('sudoChallenge');
});
