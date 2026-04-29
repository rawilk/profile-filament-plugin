<?php

declare(strict_types=1);

use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notifiable;
use Rawilk\ProfileFilament\Actions\PendingUserEmails\UpdateUserEmailAction;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Notifications\Emails\NoticeOfEmailChangeRequest;
use Rawilk\ProfileFilament\Notifications\Emails\VerifyEmailChange;
use Rawilk\ProfileFilament\Tests\TestSupport\Factories\UserFactory;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    config()->set('profile-filament.models.pending_user_email', PendingUserEmail::class);

    Filament::setCurrentPanel('admin');

    $this->action = UpdateUserEmailAction::class;
});

describe('no email verification', function () {
    beforeEach(function () {
        Filament::getCurrentPanel()->emailChangeVerification(false);
    });

    it('updates a user email address', function () {
        $user = BasicUser::factory()->create(['email' => 'user@example.test']);

        app($this->action)($user, 'new@example.test');

        expect($user->refresh())->email->toBe('new@example.test');

        Notification::assertNothingSent();
        Notification::assertSentOnDemandTimes(VerifyEmailChange::class, 0);
    });
});

describe('MustVerifyEmail only', function () {
    beforeEach(function () {
        Filament::getCurrentPanel()->emailChangeVerification(false);
    });

    it('invalidates an email verification status for MustVerifyEmail users', function () {
        $this->freezeSecond();

        $user = MustVerifyUser::factory()->create(['email' => 'user@example.test']);

        app($this->action)($user, 'new@example.test');

        expect($user->refresh())
            ->email->toBe('new@example.test')
            ->email_verified_at->toBeNull();

        assertDatabaseCount(PendingUserEmail::class, 0);

        Notification::assertSentTo($user, function (VerifyEmail $notification) use ($user) {
            expect($notification->url)->toBe(ProfileFilament::getEmailVerificationUrl($user));

            return true;
        });
    });
});

describe('email change verification', function () {
    beforeEach(function () {
        Filament::getCurrentPanel()->emailChangeVerification(true);
    });

    it('stores a pending email change for later', function () {
        $this->freezeSecond();

        $user = User::factory()->create(['email' => 'user@example.test']);

        app($this->action)($user, 'new@example.test');

        expect($user->refresh())
            ->email->toBe('user@example.test')
            ->email_verified_at->not->toBeNull();

        assertDatabaseHas(PendingUserEmail::class, [
            'user_id' => $user->getKey(),
            'email' => 'new@example.test',
        ]);

        $record = PendingUserEmail::forUser($user)->first();

        Notification::assertSentTo($user, function (NoticeOfEmailChangeRequest $notification) use ($record) {
            // We can't check for an exact url match because we're encrypting the user's email in the url.
            $url = (fn () => $this->blockVerificationUrl)->call($notification);
            expect($url)->toContain("token={$record->token}");

            return true;
        });

        Notification::assertSentOnDemand(VerifyEmailChange::class, function (VerifyEmailChange $notification, array $channels, AnonymousNotifiable $notifiable) use ($record) {
            expect($notifiable->routes['mail'])->toBe('new@example.test')
                // We can't check for an exact url match because we're encrypting the user's email in the url.
                ->and($notification->url)->toContain("token={$record->token}");

            return true;
        });

        Notification::assertNotSentTo($user, VerifyEmail::class);
    });
});

class BasicUserFactory extends UserFactory
{
    protected $model = BasicUser::class;
}

class BasicUser extends Illuminate\Foundation\Auth\User
{
    use HasFactory;

    protected $table = 'users';

    protected $guarded = [];

    protected static function newFactory(): BasicUserFactory
    {
        return BasicUserFactory::new();
    }
}

class MustVerifyUserFactory extends UserFactory
{
    protected $model = MustVerifyUser::class;
}

class MustVerifyUser extends Illuminate\Foundation\Auth\User implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    protected static function newFactory(): MustVerifyUserFactory
    {
        return MustVerifyUserFactory::new();
    }
}
