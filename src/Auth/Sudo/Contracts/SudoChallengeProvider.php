<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Contracts;

use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;

interface SudoChallengeProvider
{
    public function isEnabled(Authenticatable $user): bool;

    public function getId(): string;

    public function getChallengeFormComponents(Authenticatable $user, string $authenticateAction = 'authenticate'): array;

    public function heading(Authenticatable $user): ?string;

    public function icon(): null|string|BackedEnum|Htmlable;

    public function getChallengeSubmitLabel(): ?string;

    public function getChangeToProviderLabel(): string;
}
