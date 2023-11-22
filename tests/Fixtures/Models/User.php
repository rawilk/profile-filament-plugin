<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\Fixtures\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as BaseUser;
use Rawilk\ProfileFilament\Concerns\TwoFactorAuthenticatable;
use Rawilk\ProfileFilament\Tests\Fixtures\database\factories\UserFactory;

class User extends BaseUser implements FilamentUser
{
    use HasFactory;
    use TwoFactorAuthenticatable;

    protected $guarded = [];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
