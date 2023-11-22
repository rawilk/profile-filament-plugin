<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Events\RecoveryCodesRegenerated;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\RecoveryCodes;
use Rawilk\ProfileFilament\Support\RecoveryCode;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    disableSudoMode();

    login($this->user = User::factory()->withMfa()->create());

    $codes = [
        'code-one',
        'code-two',
        'code-three',
        'code-four',
    ];

    $this->user->update([
        'two_factor_recovery_codes' => Crypt::encryptString(
            json_encode($codes),
        ),
    ]);
});

it("shows a user's recovery codes", function () {
    livewire(RecoveryCodes::class)
        ->assertSeeInOrder([
            'code-one',
            'code-two',
            'code-three',
            'code-four',
        ]);
});

it('can generate new recovery codes', function () {
    RecoveryCode::generateCodesUsing(fn () => 'my-code');

    livewire(RecoveryCodes::class)
        ->callAction('generate')
        ->assertSet('regenerated', true)
        ->assertNotified()
        ->assertDontSee('code-one')
        ->assertSee('my-code');

    Event::assertDispatched(RecoveryCodesRegenerated::class);
});

it('requires sudo mode to generate new codes', function () {
    enableSudoMode();

    livewire(RecoveryCodes::class)
        ->call('mountAction', 'generate')
        ->assertActionMounted('sudoChallenge')
        ->assertSee('code-one');
});

it('has a copy codes to clipboard action', function () {
    livewire(RecoveryCodes::class)
        ->assertActionExists('copy');
});

it('has a download codes action', function () {
    config(['app.name' => 'Acme']);

    livewire(RecoveryCodes::class)
        ->assertActionExists('download')
        ->callAction('download')
        ->assertFileDownloaded(
            filename: 'acme-recovery-codes.txt',
            content: implode(PHP_EOL, $this->user->recoveryCodes()),
        );
});

it('has a print action', function () {
    livewire(RecoveryCodes::class)
        ->assertActionExists('print');
});
