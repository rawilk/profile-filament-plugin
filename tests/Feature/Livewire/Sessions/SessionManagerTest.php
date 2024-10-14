<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Livewire\Sessions\SessionManager;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config([
        'session.driver' => 'database',
    ]);

    $migration = require __DIR__ . '/../../../Fixtures/database/migrations/create_sessions_table.php';
    (new $migration)->up();

    login($this->user = User::factory()->create(['password' => 'secret']));
});

it('renders', function () {
    config([
        'session.driver' => 'file',
    ]);

    livewire(SessionManager::class)
        ->assertSuccessful();
});

it('lists active user sessions when using the database driver', function () {
    makeSession();
    makeSession();

    livewire(SessionManager::class)
        ->assertSuccessful()
        ->assertSeeText('Chrome')
        ->assertSeeText('127.0.0.1')
        ->assertActionVisible('revokeSession');
});

it('can remove a session by id', function () {
    makeSession();
    makeSession();

    $session = DB::table('sessions')->first();

    livewire(SessionManager::class)
        ->callAction('revokeSession', data: [
            'password' => 'secret',
        ], arguments: [
            'record' => Crypt::encryptString($session->id),
        ])
        ->assertSuccessful()
        ->assertHasNoActionErrors()
        ->assertNotified();

    // Test session does not get inserted into the testing db, so count should be 1 now.
    $this->assertDatabaseCount('sessions', 1);

    $this->assertDatabaseMissing('sessions', [
        'id' => $session->id,
    ]);
});

test('correct password is required to remove a session', function () {
    makeSession();

    $session = DB::table('sessions')->first();

    livewire(SessionManager::class)
        ->callAction('revokeSession', data: [
            'password' => 'invalid',
        ], arguments: [
            'record' => Crypt::encryptString($session->id),
        ])
        ->assertHasActionErrors([
            'password' => ['current_password'],
        ]);

    $this->assertDatabaseHas('sessions', [
        'id' => $session->id,
    ]);
});

it('can revoke all other sessions', function () {
    makeSession();
    makeSession();

    livewire(SessionManager::class)
        ->callInfolistAction('.revokeAllSessionsAction', 'revokeAllSessions', data: [
            'password' => 'secret',
        ])
        ->assertHasNoInfolistActionErrors();

    // Test session is not inserted into our test db, so the count should be 0.
    $this->assertDatabaseCount('sessions', 0);

    $this->assertAuthenticated();
});

test('current password is required to revoke all sessions', function () {
    makeSession();

    livewire(SessionManager::class)
        ->callInfolistAction('.revokeAllSessionsAction', 'revokeAllSessions', data: [
            'password' => 'invalid',
        ])
        ->assertHasInfolistActionErrors([
            'password' => ['current_password'],
        ]);

    $this->assertDatabaseCount('sessions', 1);
});

// Helpers

function makeSession(?User $user = null): void
{
    $user ??= test()->user;

    $payload = [
        'password_hash_web' => $user->getAuthPassword(),
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
