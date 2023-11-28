<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\Fixtures\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as BaseUser;
use Illuminate\Notifications\Notifiable;
use Rawilk\ProfileFilament\Tests\Fixtures\database\factories\VerifyEmailUserFactory;

class VerifyEmailUser extends BaseUser implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'immutable_datetime',
    ];

    protected static function newFactory(): VerifyEmailUserFactory
    {
        return VerifyEmailUserFactory::new();
    }
}
