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
                $publicKey,
            );

            $timebox->returnEarly();

            return $publicKey;
        }, microseconds: 300 * 1000);

        return response()->json(
            Webauthn::serializePublicKeyOptionsForRequest($publicKey),
        );
    }

    public function attestationPublicKey(Request $request): Response
    {
        $publicKey = App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($request) {
            $publicKey = Webauthn::passkeyAttestationObjectFor(
                user: auth()->user(),
                excludeCredentials: $request->input('exclude', []),
            );

            session()->put(
                MfaSession::PasskeyAttestationPk->value,
                $publicKey,
            );

            $timebox->returnEarly();

            return $publicKey;
        }, microseconds: 300 * 1000);

        return response()->json(
            Webauthn::serializePublicKeyOptionsForRequest($publicKey)
        );
    }
}
