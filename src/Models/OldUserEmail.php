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
use Rawilk\ProfileFilament\Events\PendingUserEmails\EmailAddressReverted;
use Rawilk\ProfileFilament\Exceptions\PendingUserEmails\InvalidRevertLinkException;

/**
 * @property int $id
 * @property string $user_type
 * @property int $user_id
 * @property string $email
 * @property string $token
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property-read string $revert_url
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Rawilk\ProfileFilament\Models\OldUserEmail forUser(\Illuminate\Database\Eloquent\Model $user)
 */
class OldUserEmail extends Model
{
    use HasFactory;
    use MassPrunable;
    use Tappable;

    const UPDATED_AT = null;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('profile-filament.table_names.old_user_email'));
    }

    public function scopeForUser(Builder $query, Model $user): void
    {
        $query->where([
            $this->qualifyColumn('user_type') => $user->getMorphClass(),
            $this->qualifyColumn('user_id') => $user->getKey(),
        ]);
    }

    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->sub(config('profile-filament.pending_email_changes.revert_expiration')));
    }

    public function isExpired(): bool
    {
        return $this->created_at->add(config('profile-filament.pending_email_changes.revert_expiration'))->isPast();
    }

    public function activate(): void
    {
        $user = $this->user;

        // Although this theoretically shouldn't happen, we still need to make sure the user's previous email
        // address hasn't been assigned to another user account.
        $this->guardAgainstTakenEmails();

        // Make sure token is not expired.
        $this->ensureTokenIsValid();

        $revertedFrom = $user->email;

        $user->forceFill([
            'email' => $this->email,
        ])->save();

        if ($user instanceof MustVerifyEmail) {
            $user->markEmailAsVerified();
        }

        static::whereEmail($this->email)->cursor()->each->delete();

        EmailAddressReverted::dispatch($user, $revertedFrom, $this->email);
    }

    protected function revertUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $panel = filament()->getCurrentPanel() ?? filament()->getDefaultPanel();
                $panelId = $panel->getId();

                return URL::temporarySignedRoute(
                    name: "filament.{$panelId}.pending_email.revert",
                    expiration: now()->add(config('profile-filament.pending_email_changes.revert_expiration')),
                    parameters: [
                        'token' => $this->token,
                    ],
                );
            },
        );
    }

    protected function ensureTokenIsValid(): void
    {
        throw_if(
            $this->isExpired(),
            new InvalidRevertLinkException(__('profile-filament::pages/settings.email.invalid_revert_link')),
        );
    }

    protected function guardAgainstTakenEmails(): void
    {
        $emailExists = DB::table($this->user->getTable())
            ->where('email', $this->email)
            ->exists();

        throw_if(
            $emailExists,
            new InvalidRevertLinkException(__('profile-filament::pages/settings.email.email_already_taken')),
        );
    }
}
