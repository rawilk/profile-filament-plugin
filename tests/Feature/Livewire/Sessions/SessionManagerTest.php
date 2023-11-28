<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Livewire\Sessions\SessionManager;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config([
        'session.driver' => 'database',
    ]);

    $migration = require __DIR__ . '/../../../Fixtures/database/migrations/create_sessions_table.php';
    (new $migration)->up();

    $this->user = User::factory()->create(['password' => 'secret']);
});

it('lists active user sessions when using the database driver', function () {
    login($this->user);

    makeSession($this->user);

    livewire(SessionManager::class)
        ->assertSuccessful()
        ->assertSeeText('Chrome')
        ->assertSeeText('127.0.0.1');
});

it('can remove a session by id', function () {
    login($this->user);

    makeSession($this->user);
    makeSession($this->user);

    $session = DB::table('sessions')->first();

    livewire(SessionManager::class)
        ->callAction(
            name: 'revoke',
            data: ['password' => 'secret'],
            arguments: ['session' => Crypt::encryptString($session->id)],
        )
        ->assertSuccessful()
        ->assertHasNoActionErrors()
        ->assertNotified();

    // Current session is not being inserted into our testing db, so count should be 1 now.
    $this->assertDatabaseCount('sessions', 1);
});

test('correct password is required to remove a session', function () {
    login($this->user);

    makeSession($this->user);

    $session = DB::table('sessions')->first();

    livewire(SessionManager::class)
        ->callAction(
            name: 'revoke',
            data: ['password' => 'incorrect'],
            arguments: ['session' => Crypt::encryptString($session->id)],
        )
        ->assertHasActionErrors([
            'password' => 'current-password',
        ]);

    $this->assertDatabaseCount('sessions', 1);
});

it('can revoke all other sessions', function () {
    login($this->user);

    makeSession($this->user);
    makeSession($this->user);

    livewire(SessionManager::class)
        ->callAction('revokeAll', [
            'password' => 'secret',
        ])
        ->assertSuccessful()
        ->assertHasNoActionErrors()
        ->assertNotified();

    // Current session is not inserted into the testing db...
    $this->assertDatabaseCount('sessions', 0);

    expect(auth()->check())->toBeTrue();
});

test('correct password is required to revoke all sessions', function () {
    login($this->user);

    makeSession($this->user);

    livewire(SessionManager::class)
        ->callAction('revokeAll', [
            'password' => 'incorrect',
        ])
        ->assertHasActionErrors([
            'password' => 'current-password',
        ]);

    $this->assertDatabaseCount('sessions', 1);
});

function makeSession(User $user): void
{
    $payload = [
        'password_hash_web' => $user->password,
    ];

    DB::table('sessions')
        ->insert([
            'id' => Str::ulid(),
            'user_id' => $user->id,
            'last_activity' => now()->unix(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1667.0 Safari/537.36',
            'payload' => base64_encode(serialize($payload)),
        ]);
}
