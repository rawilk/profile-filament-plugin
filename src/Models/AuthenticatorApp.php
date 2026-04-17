<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $secret
 * @property null|\Illuminate\Support\Carbon $last_used_at
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 */
class AuthenticatorApp extends Model
{
    use Concerns\HasAuthenticatorTimestamps;
    use HasFactory;

    protected $hidden = [
        'secret',
    ];

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('profile-filament.table_names.authenticator_apps');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'last_used_at' => 'immutable_datetime',
        ];
    }
}
