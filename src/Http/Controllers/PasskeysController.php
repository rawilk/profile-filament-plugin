<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Timebox;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Symfony\Component\HttpFoundation\Response;

class PasskeysController
{
    public function assertionPublicKey(): Response
    {
        $publicKey = App::make(Timebox::class)->call(callback: function (Timebox $timebox) {
            $publicKey = Webauthn::passkeyAssertionObject();

            session()->put(
                MfaSession::PasskeyAssertionPk->value,
                serialize($publicKey),
            );

            $timebox->returnEarly();

            return $publicKey;
        }, microseconds: 300 * 1000);

        return response()->json($publicKey->jsonSerialize());
    }

    public function attestationPublicKey(Request $request): Response
    {
        $publicKey = App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($request) {
            $model = config('profile-filament.models.webauthn_key');
            $publicKey = Webauthn::passkeyAttestationObjectFor(
                username: app($model)::getUsername(auth()->user()),
                userId: app($model)::getUserHandle(auth()->user()),
                excludeCredentials: $request->get('exclude', []),
            );

            session()->put(
                MfaSession::PasskeyAttestationPk->value,
                serialize($publicKey),
            );

            $timebox->returnEarly();

            return $publicKey;
        }, microseconds: 300 * 1000);

        return response()->json($publicKey->jsonSerialize());
    }
}
