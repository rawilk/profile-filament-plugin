<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Symfony\Component\HttpFoundation\Response;

class WebauthnPublicKeysController
{
    public function assertionPublicKey($user, Request $request): Response
    {
        $user = app(config('auth.providers.users.model'))::findOrFail($user);

        $model = config('profile-filament.models.webauthn_key');
        $publicKey = Webauthn::assertionObjectFor(
            userId: app($model)::getUserHandle($user),
        );

        session()->put(
            $request->get('s') ?? MfaSession::AssertionPublicKey->value,
            serialize($publicKey),
        );

        return response()->json($publicKey->jsonSerialize());
    }

    public function attestationPublicKey(): Response
    {
        $model = config('profile-filament.models.webauthn_key');
        $publicKey = Webauthn::attestationObjectFor(
            username: app($model)::getUsername(auth()->user()),
            userId: app($model)::getUserHandle(auth()->user()),
        );

        session()->put(MfaSession::AttestationPublicKey->value, serialize($publicKey));

        return response()->json($publicKey->jsonSerialize());
    }
}
