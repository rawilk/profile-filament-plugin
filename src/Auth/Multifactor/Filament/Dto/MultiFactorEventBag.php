<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use SensitiveParameter;

class MultiFactorEventBag implements MultiFactorEventBagContract
{
    public ?Authenticatable $user = null;

    protected array $data = [];

    protected bool $remember = false;

    protected Request $request;

    public function getData(): array
    {
        return $this->data;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setData(#[SensitiveParameter] array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function setRemember(bool $condition): static
    {
        $this->remember = $condition;

        return $this;
    }

    public function setUser(?Authenticatable $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function shouldRememberUser(): bool
    {
        return $this->remember;
    }

    public function user(): ?Authenticatable
    {
        return $this->user ?? auth()->user();
    }

    public function getAuthGuard(): StatefulGuard
    {
        return Filament::auth();
    }

    public function getUserProvider(): UserProvider
    {
        return $this->getAuthGuard()->getProvider();
    }
}
