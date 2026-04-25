<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config as LaravelConfig;
use LogicException;

final class Config
{
    public static function hashUserPasswords(): bool
    {
        return LaravelConfig::boolean('profile-filament.hash_user_passwords');
    }

    /**
     * @return class-string<Authenticatable>
     */
    public static function getAuthenticatableModel(): string
    {
        $model = config('auth.providers.users.model');

        if (! is_a($model, Authenticatable::class, true)) {
            throw new LogicException('The authenticatable model [' . $model . '] must implement the [' . Authenticatable::class . '] interface');
        }

        return $model;
    }

    /**
     * @template T
     *
     * @param  class-string<T>  $actionBaseClass
     * @return class-string<T>
     */
    public static function getWebauthnActionClass(string $actionName, string $actionBaseClass): string
    {
        $actionClass = config("profile-filament.webauthn.actions.{$actionName}") ?? $actionBaseClass;

        self::ensureValidActionClass($actionName, $actionBaseClass, $actionClass);

        return $actionClass;
    }

    /**
     * @template T
     *
     * @param  class-string<T>  $actionBaseClass
     * @return T
     */
    public static function getWebauthnAction(string $actionName, string $actionBaseClass)
    {
        $actionClass = self::getWebauthnActionClass($actionName, $actionBaseClass);

        return app($actionClass);
    }

    /**
     * @return class-string
     */
    public static function getActionClass(string $actionName): string
    {
        return config("profile-filament.actions.{$actionName}");
    }

    /**
     * @return class-string
     */
    public static function getModel(string $model): string
    {
        return config("profile-filament.models.{$model}");
    }

    public static function getTableName(string $table): string
    {
        return config("profile-filament.table_names.{$table}");
    }

    public static function getRelyingPartyName(): string
    {
        return config('profile-filament.webauthn.relying_party.name');
    }

    public static function getRelyingPartyId(): string
    {
        return config('profile-filament.webauthn.relying_party.id');
    }

    public static function getRelyingPartyIcon(): ?string
    {
        return config('profile-filament.webauthn.relying_party.icon');
    }

    private static function ensureValidActionClass(string $actionName, string $actionBaseClass, string $actionClass): void
    {
        if (! is_a($actionClass, $actionBaseClass, true)) {
            throw new LogicException("The action [{$actionName}] must extend [{$actionBaseClass}]. The configured class [{$actionClass}] does not.");
        }
    }
}
