<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\TwoFactor\GenerateNewRecoveryCodesAction;
use Rawilk\ProfileFilament\Events\RecoveryCodesRegenerated;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\RecoveryCodes;
use Rawilk\ProfileFilament\Support\RecoveryCode;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    disableSudoMode();

    $codes = [
        'code-one',
        'code-two',
        'code-three',
        'code-four',
    ];

    login(
        $this->user = User::factory()->withMfa()->create([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ])
    );
});

afterEach(function () {
    RecoveryCode::generateCodesUsing(null);
});

it('renders', function () {
    livewire(RecoveryCodes::class)
        ->assertSuccessful();
});

it('shows a users recovery codes', function () {
    livewire(RecoveryCodes::class)
        ->assertSeeTextInOrder([
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
        ->assertDontSeeText('code-one')
        ->assertSeeText('my-code');

    Event::assertDispatched(RecoveryCodesRegenerated::class);
});

it('can require sudo mode to regenerate recovery codes', function () {
    enableSudoMode();

    $this->mock(GenerateNewRecoveryCodesAction::class)
        ->shouldNotReceive('__invoke');

    livewire(RecoveryCodes::class)
        ->call('mountAction', 'generate')
        ->assertSeeText(sudoChallengeTitle())
        ->assertSeeText('code-one');
});

it('has a copy codes to clipboard action', function () {
    livewire(RecoveryCodes::class)->assertActionExists('copy');
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
    livewire(RecoveryCodes::class)->assertActionExists('print');
});
