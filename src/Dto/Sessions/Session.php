<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Dto\Sessions;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Rawilk\ProfileFilament\Support\Agent;

class Session
{
    public function __construct(protected object $data)
    {
    }

    public function id(): string
    {
        return once(fn (): string => Crypt::encryptString(data_get($this->data, 'id')));
    }

    public function agent(): Agent
    {
        return once(
            fn (): Agent => tap(
                new Agent,
                fn (Agent $agent) => $agent->setUserAgent(data_get($this->data, 'user_agent')),
            )
        );
    }

    public function ipAddress(): string
    {
        return data_get($this->data, 'ip_address');
    }

    public function isCurrentDevice(): bool
    {
        return data_get($this->data, 'id') === session()->getId();
    }

    public function lastActive(): string
    {
        return Date::createFromTimestampUTC(
            data_get($this->data, 'last_activity'),
        )->diffForHumans();
    }
}
