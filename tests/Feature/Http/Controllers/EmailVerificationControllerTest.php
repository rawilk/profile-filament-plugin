<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->freezeSecond();

    Filament::setCurrentPanel('admin');
});

it('can verify an email', function () {
    $userToVerify = User::factory()->unverified()->create();

    expect($userToVerify)->hasVerifiedEmail()->toBeFalse();

    actingAs($userToVerify)
        ->get(ProfileFilament::getEmailVerificationUrl($userToVerify))
        ->assertRedirect(Filament::getUrl());

    expect($userToVerify->refresh())->hasVerifiedEmail()->toBeTrue();
});

it('cannot verify an email while signed in as another user', function () {
    $userToVerify = User::factory()->unverified()->create();
    $otherUser = User::factory()->unverified()->create();

    expect($otherUser)->hasVerifiedEmail()->toBeFalse();

    actingAs($otherUser)
        ->get(ProfileFilament::getEmailVerificationUrl($userToVerify))
        ->assertForbidden();

    expect($otherUser->refresh())->hasVerifiedEmail()->toBeFalse()
        ->and($userToVerify->refresh())->hasVerifiedEmail()->toBeFalse();
});
