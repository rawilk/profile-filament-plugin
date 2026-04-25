<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Traits\Tappable;
use Rawilk\ProfileFilament\Support\Config;

/**
 * @property int $id
 * @property string $user_type
 * @property int $user_id
 * @property string $email
 * @property string $token
 * @property null|\Illuminate\Support\Carbon $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Rawilk\ProfileFilament\Models\OldUserEmail forUser(\Illuminate\Database\Eloquent\Model $user)
 */
class PendingUserEmail extends Model
{
    use HasFactory;
    use MassPrunable;
    use Tappable;

    const UPDATED_AT = null;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(Config::getTableName('pending_user_email'));
    }

    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    public function isExpired(): bool
    {
        return $this->created_at->addMinutes(config('auth.verification.expire', 60))->isPast();
    }

    public function scopeForUser(Builder $query, Model $user): void
    {
        $query->where([
            $this->qualifyColumn('user_type') => $user->getMorphClass(),
            $this->qualifyColumn('user_id') => $user->getKey(),
        ]);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMinutes(config('auth.verification.expire', 60)));
    }
}
