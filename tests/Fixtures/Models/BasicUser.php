<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as BaseUser;
use Rawilk\ProfileFilament\Tests\Fixtures\database\factories\BasicUserFactory;

final class BasicUser extends BaseUser
{
    use HasFactory;

    protected $table = 'users';

    protected $casts = [
        'password' => 'hashed',
    ];

    protected $guarded = [];

    protected static function newFactory(): BasicUserFactory
    {
        return BasicUserFactory::new();
    }
}
