<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\PendingUserEmails;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\StoreOldUserEmailAction as StoreOldUserEmailActionContract;
use Rawilk\ProfileFilament\Facades\ProfileFilament;

class StoreOldUserEmailAction implements StoreOldUserEmailActionContract
{
    protected string $oldUserEmailModel;

    public function __construct()
    {
        $this->oldUserEmailModel = config('profile-filament.models.old_user_email');
    }

    public function __invoke(User $user, string $email)
    {
        $this->clearOldEmails($user, $email);

        $oldEmail = app($this->oldUserEmailModel)->create([
            'user_type' => $user->getMorphClass(),
            'user_id' => $user->getKey(),
            'email' => $email,
            'token' => Password::broker()->getRepository()->createNewToken(), /** @phpstan-ignore-line */
        ]);

        $mailable = config('profile-filament.mail.pending_email_verified');

        Mail::to($oldEmail->email)->send(
            new $mailable(
                $user->email, /** @phpstan-ignore-line */
                $oldEmail,
                filament()->getCurrentPanel()?->getId(),
                request()?->ip(), /** @phpstan-ignore-line */
                now()->tz(ProfileFilament::userTimezone($user)),
            ),
        );
    }

    protected function clearOldEmails(User $user, string $email): void
    {
        app($this->oldUserEmailModel)::query()
            ->forUser($user)
            ->where('email', $email)
            ->cursor()
            ->each->delete();
    }
}
