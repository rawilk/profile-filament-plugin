<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\Dto;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use SensitiveParameter;

class LoginEventBag implements LoginEventBagContract
{
    public ?Authenticatable $user = null;

    protected array $data = [];

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

    public function setUser(?Authenticatable $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function user(): ?Authenticatable
    {
        return $this->user ?? auth()->user();
    }

    public function getCredentialsFromFormData(): array
    {
        return [
            'email' => $this->data['email'],
            'password' => $this->data['password'],
        ];
    }

    public function shouldRememberUser(): bool
    {
        return (bool) data_get($this->data, 'remember', false);
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
