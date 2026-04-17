<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\PendingUserEmails;

use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\UpdateUserEmailAction as UpdateUserEmailActionContract;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Filament\Actions\Emails\Concerns\SendsEmailChangeVerification;
use Rawilk\ProfileFilament\Notifications\Emails\NoticeOfEmailChangeRequest;

class UpdateUserEmailAction implements UpdateUserEmailActionContract
{
    use SendsEmailChangeVerification;

    /** @var class-string<Model> */
    protected string $pendingUserEmailModel;

    public function __construct()
    {
        $this->pendingUserEmailModel = config('profile-filament.models.pending_user_email');
    }

    public function __invoke(User $user, string $email): void
    {
        if (Str::lower($email) === Str::lower($user->getAttributeValue('email'))) {
            return;
        }

        if (Filament::hasEmailChangeVerification()) {
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

        $notification = app(VerifyEmail::class);
        $notification->url = ProfileFilament::getEmailVerificationUrl($user);

        $user->notify($notification);
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

        $user->notify(app(NoticeOfEmailChangeRequest::class, [
            'blockVerificationUrl' => ProfileFilament::getBlockEmailChangeVerificationUrl($user, $email, [
                'token' => $pendingEmail->token,
            ]),
            'newEmail' => $email,
        ]));

        $this->sendEmailChangeVerification($user, $pendingEmail);
    }

    protected function clearPendingEmails(User $user): void
    {
        $this->pendingUserEmailModel::query()
            ->forUser($user)
            ->cursor()
            ->each->delete();
    }
}
