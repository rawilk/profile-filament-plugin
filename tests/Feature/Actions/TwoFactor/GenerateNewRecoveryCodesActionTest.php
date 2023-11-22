<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\TwoFactor\GenerateNewRecoveryCodesAction;
use Rawilk\ProfileFilament\Contracts\TwoFactor\GenerateNewRecoveryCodesAction as GenerateNewRecoveryCodesActionContract;
use Rawilk\ProfileFilament\Events\RecoveryCodesRegenerated;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config()->set('profile-filament.actions.generate_new_recovery_codes', GenerateNewRecoveryCodesAction::class);
});

it('generates new recovery codes for a user', function () {
    Event::fake();

    $user = User::factory()->create([
        'two_factor_enabled' => true,
        'two_factor_recovery_codes' => null,
    ]);

    app(GenerateNewRecoveryCodesActionContract::class)($user);

    Event::assertDispatched(RecoveryCodesRegenerated::class);

    $user->fresh();

    expect($user->two_factor_recovery_codes)->not->toBeNull()
        ->and(json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true))->toBeArray();
});
