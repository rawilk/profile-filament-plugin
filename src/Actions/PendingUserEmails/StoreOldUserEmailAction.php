<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\PendingUserEmails;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\StoreOldUserEmailAction as StoreOldUserEmailActionContract;
use Rawilk\ProfileFilament\Facades\ProfileFilament;

class StoreOldUserEmailAction implements StoreOldUserEmailActionContract
{
    /** @var class-string<Model> */
    protected string $oldUserEmailModel;

    public function __construct()
    {
        $this->oldUserEmailModel = config('profile-filament.models.old_user_email');
    }

    public function __invoke(User $user, string $email)
    {
        $this->clearOldEmails($user, $email);

        $oldEmail = $this->oldUserEmailModel::make([
            'email' => $email,
            'token' => Password::broker()->getRepository()->createNewToken(),
        ]);

        $oldEmail->user()->associate($user);

        $oldEmail->save();

        $mailable = config('profile-filament.mail.pending_email_verified');

        Mail::to($oldEmail->email)->send(
            new $mailable(
                $user->email,
                $oldEmail,
                filament()->getCurrentPanel()?->getId(),
                request()?->ip(),
                now()->tz(ProfileFilament::userTimezone($user)),
            ),
        );
    }

    protected function clearOldEmails(User $user, string $email): void
    {
        $this->oldUserEmailModel::query()
            ->forUser($user)
            ->where('email', $email)
            ->cursor()
            ->each->delete();
    }
}
