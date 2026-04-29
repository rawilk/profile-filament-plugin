<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Rawilk\ProfileFilament\Events\PendingUserEmails\NewUserEmailVerified;
use Rawilk\ProfileFilament\Support\Config;

class EmailChangeVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! hash_equals((string) $this->user()->getRouteKey(), (string) $this->route('id'))) {
            return false;
        }

        if (blank($this->query('token'))) {
            return false;
        }

        try {
            return filled(Crypt::decryptString($this->route('email')));
        } catch (DecryptException) {
            return false;
        }
    }

    public function fulfill(): void
    {
        /** @var \Rawilk\ProfileFilament\Models\PendingUserEmail $pendingEmail */
        $pendingEmail = app(Config::getModel('pending_user_email'))->whereToken($this->query('token'))->firstOr(['*'], function () {
            throw new AuthorizationException;
        });

        $newEmail = Crypt::decryptString($this->route('email'));
        throw_unless(
            hash_equals($pendingEmail->email, $newEmail),
            AuthorizationException::class,
        );

        throw_if(
            $pendingEmail->isExpired(),
            AuthorizationException::class,
        );

        /** @var Model $user */
        $user = $this->user();
        $previousEmail = $user->email;

        $this->guardAgainstTakenEmails($user, $newEmail);

        $user->update([
            'email' => $newEmail,
        ]);

        if (method_exists($user, 'markEmailAsVerified')) {
            $user->markEmailAsVerified();
        }

        $pendingEmail->delete();

        NewUserEmailVerified::dispatch($user, $previousEmail);
    }

    protected function guardAgainstTakenEmails(Model $user, string $newEmail): void
    {
        $emailExists = DB::table($user->getTable())
            ->where('email', $newEmail)
            ->exists();

        throw_if(
            $emailExists,
            AuthorizationException::class,
        );
    }
}
