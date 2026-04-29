<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\TestSupport\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Concerns\InteractsWithAppAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Concerns\InteractsWithMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Concerns\InteractsWithEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Concerns\InteractsWithAuthenticationRecovery;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Concerns\InteractsWithWebauthn;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn;
use Rawilk\ProfileFilament\Tests\TestSupport\Factories\UserFactory;

class User extends \Illuminate\Foundation\Auth\User implements FilamentUser, HasAppAuthentication, HasEmailAuthentication, HasMultiFactorAuthentication, HasMultiFactorAuthenticationRecovery, HasWebauthn, MustVerifyEmail
{
    use HasFactory;
    use InteractsWithAppAuthentication;
    use InteractsWithAuthenticationRecovery;
    use InteractsWithEmailAuthentication;
    use InteractsWithMultiFactorAuthentication;
    use InteractsWithWebauthn;
    use Notifiable;

    protected $guarded = [];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
