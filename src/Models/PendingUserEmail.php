<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Traits\Tappable;
use Rawilk\ProfileFilament\Contracts\PendingUserEmail\StoreOldUserEmailAction;
use Rawilk\ProfileFilament\Events\PendingUserEmails\NewUserEmailVerified;
use Rawilk\ProfileFilament\Exceptions\PendingUserEmails\InvalidVerificationLinkException;

class PendingUserEmail extends Model
{
    use HasFactory;
    use MassPrunable;
    use Tappable;

    const UPDATED_AT = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('profile-filament.table_names.pending_user_email'));
    }

    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    public function scopeForUser(Builder $query, Model $user): void
    {
        $query->where([
            $this->qualifyColumn('user_type') => $user->getMorphClass(),
            $this->qualifyColumn('user_id') => $user->getKey(),
        ]);
    }

    public function activate(): void
    {
        $user = $this->user;

        // Although this theoretically shouldn't happen, make sure the new email hasn't already been assigned
        // to a user account.
        $this->guardAgainstTakenEmails();

        $originalEmail = $user->email;

        $user->forceFill([
            'email' => $this->email,
        ])->save();

        if ($user instanceof MustVerifyEmail) {
            $user->markEmailAsVerified();
        }

        static::whereEmail($this->email)->cursor()->each->delete();

        // Store old email in case the user needs to revert back to it,
        // and also send an email notification to it.
        app(StoreOldUserEmailAction::class)($user, $originalEmail);

        NewUserEmailVerified::dispatch($user, $originalEmail);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMinutes(config('auth.verification.expire', 60)));
    }

    protected function verificationUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $panel = filament()->getCurrentPanel() ?? filament()->getDefaultPanel();
                $panelId = $panel->getId();

                return URL::temporarySignedRoute(
                    name: "filament.{$panelId}.pending_email.verify",
                    expiration: now()->addMinutes(config('auth.verification.expire', 60)),
                    parameters: [
                        'token' => $this->token,
                    ],
                );
            },
        );
    }

    protected function guardAgainstTakenEmails(): void
    {
        $emailExists = DB::table($this->user->getTable())
            ->where('email', $this->email)
            ->exists();

        throw_if(
            $emailExists,
            new InvalidVerificationLinkException(__('profile-filament::pages/settings.email.email_already_taken')),
        );
    }
}
