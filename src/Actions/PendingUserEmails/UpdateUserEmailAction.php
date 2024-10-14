<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\PendingUserEmails;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\MustVerifyNewEmail;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\UpdateUserEmailAction as UpdateUserEmailActionContract;

class UpdateUserEmailAction implements UpdateUserEmailActionContract
{
    /** @var class-string<Model> */
    protected string $pendingUserEmailModel;

    public function __construct()
    {
        $this->pendingUserEmailModel = config('profile-filament.models.pending_user_email');
    }

    public function __invoke(User $user, string $email)
    {
        if ($email === $user->email) {
            return;
        }

        if ($user instanceof MustVerifyNewEmail) {
            $this->storePendingEmail($user, $email);

            return;
        }

        if ($user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $email);

            return;
        }

        $user->forceFill([
            'email' => $email,
        ])->save();
    }

    protected function updateVerifiedUser(User $user, string $email): void
    {
        $user->forceFill([
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    protected function storePendingEmail(User $user, string $email): void
    {
        $this->clearPendingEmails($user);

        $pendingEmail = $this->pendingUserEmailModel::make([
            'email' => $email,
            'token' => Password::broker()->getRepository()->createNewToken(),
        ]);

        $pendingEmail->user()->associate($user);

        $pendingEmail->save();

        $verificationMailable = config('profile-filament.mail.pending_email_verification');

        Mail::to($pendingEmail->email)->send(
            new $verificationMailable($pendingEmail, filament()->getCurrentPanel()?->getId()),
        );
    }

    protected function clearPendingEmails(User $user): void
    {
        $this->pendingUserEmailModel::query()
            ->forUser($user)
            ->cursor()
            ->each->delete();
    }
}
