<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use SensitiveParameter;

interface MultiFactorEventBagContract
{
    public function getData(): array;

    public function getRequest(): Request;

    public function setData(#[SensitiveParameter] array $data): static;

    public function setRequest(Request $request): static;

    public function setRemember(bool $condition): static;

    public function setUser(?Authenticatable $user): static;

    public function shouldRememberUser(): bool;

    public function user(): ?Authenticatable;

    public function getAuthGuard(): StatefulGuard;

    public function getUserProvider(): UserProvider;
}
