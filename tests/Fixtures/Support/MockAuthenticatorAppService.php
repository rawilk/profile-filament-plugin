<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\Fixtures\Support;

use Rawilk\ProfileFilament\Services\AuthenticatorAppService;

class MockAuthenticatorAppService extends AuthenticatorAppService
{
    public static string $secret;

    public function generateSecretKey(): string
    {
        return static::$secret;
    }
}
