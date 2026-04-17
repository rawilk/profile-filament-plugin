<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts;

interface HasEmailAuthentication
{
    public function hasEmailAuthentication(): bool;

    public function toggleEmailAuthentication(bool $condition): void;
}
