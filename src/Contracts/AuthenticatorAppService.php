<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts;

interface AuthenticatorAppService
{
    public function generateSecretKey(): string;

    public function qrCodeUrl(string $companyName, string $companyEmail, string $secret): string;

    public function qrCodeSvg(string $url): string;

    public function verify(string $secret, string $code, bool $withoutTimestamps = false): bool;
}
