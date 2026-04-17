<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Support\SudoChallengeProviders;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Livewire\Component;
use Rawilk\ProfileFilament\Dto\SudoChallengeAssertions\SudoChallengeAssertion;
use SensitiveParameter;

/** @deprecated */
interface SudoChallengeProvider
{
    /**
     * Is the challenge mode allowed/available for a given user?
     */
    public static function allowedFor(?User $user = null): bool;

    public static function submitIsHidden(?User $user = null): bool;

    public static function submitLabel(?User $user = null): string;

    public static function heading(?User $user = null): ?string;

    public static function icon(): ?string;

    public static function linkLabel(?User $user = null): string;

    public static function slug(): string;

    public static function schema(Component $livewire): array;

    public static function assert(
        #[SensitiveParameter] array $data,
        ?User $user,
        Request $request,
        #[SensitiveParameter] ?array $extra = null,
    ): SudoChallengeAssertion;
}
