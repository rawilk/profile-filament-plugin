<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Livewire\MaskedValue;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'secret@secret.com']);
});

it('initially shows the masked value only', function () {
    livewire(MaskedValue::class, [
        'maskedValue' => '***@**.com',
        'model' => $this->user,
        'field' => 'email',
    ])
        ->assertSee('***@**.com')
        ->assertDontSee('secret@secret.com')
        ->assertActionExists('reveal');
});

it('can reveal the masked value', function () {
    livewire(MaskedValue::class, [
        'maskedValue' => '***@**.com',
        'model' => $this->user,
        'field' => 'email',
    ])
        ->callAction('reveal')
        ->assertDontSee('***@**.com')
        ->assertSee('secret@secret.com');
});

test('sudo mode can be required to reveal the value', function () {
    $otherUser = User::factory()->create(['email' => 'other@secret.com']);

    login($this->user);

    livewire(MaskedValue::class, [
        'maskedValue' => '***@**.com',
        'model' => $otherUser,
        'field' => 'email',
        'requiresSudo' => true,
    ])
        ->call('mountAction', 'reveal')
        ->assertActionMounted('sudoChallenge')
        ->assertDontSee('other@secret.com');
});
