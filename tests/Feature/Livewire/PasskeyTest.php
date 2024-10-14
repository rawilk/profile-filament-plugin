<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\Passkeys\DeletePasskeyAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyDeleted;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyUpdated;
use Rawilk\ProfileFilament\Livewire\Passkey;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    disableSudoMode();

    config([
        'profile-filament.actions.delete_passkey' => DeletePasskeyAction::class,
        'profile-filament.actions.mark_two_factor_disabled' => MarkTwoFactorDisabledAction::class,
    ]);

    $this->passkey = WebauthnKey::factory()->passkey()->for(User::factory()->withMfa())->create([
        'name' => 'my passkey',
    ]);

    login($this->passkey->user);
});

it('renders', function () {
    livewire(Passkey::class, [
        'passkey' => $this->passkey,
    ])
        ->assertSuccessful()
        ->assertSeeText('my passkey');
});

it('can edit a passkey name', function () {
    livewire(Passkey::class, [
        'passkey' => $this->passkey,
    ])
        ->mountAction('edit')
        ->assertActionDataSet([
            'name' => 'my passkey',
        ])
        ->callAction('edit', [
            'name' => 'new name',
        ])
        ->assertHasNoActionErrors();

    Event::assertDispatched(PasskeyUpdated::class);

    expect($this->passkey->refresh())->name->toBe('new name');
});

test('name is required', function () {
    livewire(Passkey::class, [
        'passkey' => $this->passkey,
    ])
        ->callAction('edit', [
            'name' => null,
        ])
        ->assertHasActionErrors([
            'name' => ['required'],
        ]);

    expect($this->passkey->refresh())->name->toBe('my passkey');
});

it('requires a unique name', function () {
    WebauthnKey::factory()->notPasskey()->for($this->passkey->user)->create(['name' => 'taken name']);

    livewire(Passkey::class, [
        'passkey' => $this->passkey,
    ])
        ->callAction('edit', [
            'name' => 'taken name',
        ])
        ->assertHasActionErrors([
            'name' => ['unique'],
        ]);

    expect($this->passkey->refresh())->name->toBe('my passkey');
});

test('a user cannot edit another users passkey', function () {
    $otherPasskey = WebauthnKey::factory()->passkey()->for(User::factory())->create();

    livewire(Passkey::class, [
        'passkey' => $otherPasskey,
    ])
        ->assertActionDisabled('edit');
});

it('can delete a passkey', function () {
    livewire(Passkey::class, [
        'passkey' => $this->passkey,
    ])
        ->callAction('delete')
        ->assertDispatched(MfaEvent::PasskeyDeleted->value, id: $this->passkey->getKey())
        ->assertSet('passkey', null);

    Event::assertDispatched(PasskeyDeleted::class);

    $this->assertModelMissing($this->passkey);
});

it('can require sudo mode to delete a passkey', function () {
    enableSudoMode();

    livewire(Passkey::class, [
        'passkey' => $this->passkey,
    ])
        ->call('mountAction', 'delete')
        ->assertSeeText(sudoChallengeTitle());
});

test('a user cannot delete another users passkey', function () {
    $otherPasskey = WebauthnKey::factory()->passkey()->for(User::factory())->create();

    livewire(Passkey::class, [
        'passkey' => $otherPasskey,
    ])
        ->assertActionDisabled('delete');
});
