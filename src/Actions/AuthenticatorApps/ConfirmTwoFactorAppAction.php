<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\AuthenticatorApps;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Contracts\AuthenticatorApps\ConfirmTwoFactorAppAction as ConfirmTwoFactorAppActionContract;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppAdded;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class ConfirmTwoFactorAppAction implements ConfirmTwoFactorAppActionContract
{
    /** @var class-string<\Illuminate\Database\Eloquent\Model> */
    protected string $model;

    public function __construct()
    {
        $this->model = config('profile-filament.models.authenticator_app');
    }

    public function __invoke(User $user, string $name, string $secret)
    {
        $authenticator = tap(app($this->model)::make(), function (AuthenticatorApp $authenticator) use ($user, $name, $secret) {
            $authenticator->fill([
                'name' => $name,
                'secret' => $secret,
            ]);

            $authenticator->user()->associate($user);

            $authenticator->save();
        });

        app(MarkTwoFactorEnabledAction::class)($user);

        TwoFactorAppAdded::dispatch($user, $authenticator);
    }
}
