<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Events\RecoveryCodesViewed;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    Event::fake();

    $this->user = User::factory()->withMfa()->create();

    disableSudoMode();

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

describe('recovery codes', function () {
    it('can be printed', function () {
        login($this->user);

        get(route('filament.admin.auth.mfa.recovery-codes.print'))
            ->assertSuccessful()
            ->assertSeeInOrder([
                'code-one',
                'code-two',
                'code-three',
                'code-four',
            ]);

        Event::assertDispatched(RecoveryCodesViewed::class);
    });

    it('requires sudo mode', function () {
        enableSudoMode();

        login($this->user);

        get(route('filament.admin.auth.mfa.recovery-codes.print'))
            ->assertRedirectToRoute('filament.admin.auth.sudo-challenge');

        Event::assertNotDispatched(RecoveryCodesViewed::class);
        Event::assertDispatched(SudoModeChallenged::class);
    });

    test('guests are not allowed to see this view', function () {
    get(route('filament.admin.auth.mfa.recovery-codes.print'))
    ->assertRedirect('/admin/login');

        Event::assertNotDispatched(RecoveryCodesViewed::class);
        Event::assertNotDispatched(SudoModeChallenged::class);
        });
});
