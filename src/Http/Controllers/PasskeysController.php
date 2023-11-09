<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Symfony\Component\HttpFoundation\Response;

class PasskeysController
{
    public function assertionPublicKey(): Response
    {
        $publicKey = Webauthn::passkeyAssertionObject();

        session()->put(MfaSession::PasskeyAssertionPk->value, serialize($publicKey));

        return response()->json($publicKey->jsonSerialize());
    }

    public function attestationPublicKey(Request $request): Response
    {
        $model = config('profile-filament.models.webauthn_key');
        $publicKey = Webauthn::passkeyAttestationObjectFor(
            username: app($model)::getUsername(auth()->user()),
            userId: app($model)::getUserHandle(auth()->user()),
            excludeCredentials: $request->get('exclude', []),
        );

        session()->put(MfaSession::PasskeyAttestationPk->value, serialize($publicKey));

        return response()->json($publicKey->jsonSerialize());
    }
}
