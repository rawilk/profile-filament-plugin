<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\StoreAuthenticatorAppAction as StoreAuthenticatorAppActionContract;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasCreated;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MarkMultiFactorEnabledAction;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Support\Config;

class StoreAuthenticatorAppAction implements StoreAuthenticatorAppActionContract
{
    /** @var class-string<\Illuminate\Database\Eloquent\Model> */
    protected string $model;

    public function __construct()
    {
        $this->model = Config::getModel('authenticator_app');
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

        app(MarkMultiFactorEnabledAction::class)($user);

        AuthenticatorAppWasCreated::dispatch($user, $authenticator);
    }
}
