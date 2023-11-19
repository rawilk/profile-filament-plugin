<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Timebox;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Symfony\Component\HttpFoundation\Response;

class WebauthnPublicKeysController
{
    public function assertionPublicKey($user, Request $request): Response
    {
        $publicKey = App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($user, $request) {
            $user = app(config('auth.providers.users.model'))::findOrFail($user);

            $model = config('profile-filament.models.webauthn_key');
            $publicKey = Webauthn::assertionObjectFor(
                userId: app($model)::getUserHandle($user),
            );

            session()->put(
                $request->get('s') ?? MfaSession::AssertionPublicKey->value,
                serialize($publicKey),
            );

            $timebox->returnEarly();

            return $publicKey;
        }, microseconds: 300 * 1000);

        return response()->json($publicKey->jsonSerialize());
    }

    public function attestationPublicKey(): Response
    {
        $publicKey = App::make(Timebox::class)->call(callback: function (Timebox $timebox) {
            $model = config('profile-filament.models.webauthn_key');
            $publicKey = Webauthn::attestationObjectFor(
                username: app($model)::getUsername(auth()->user()),
                userId: app($model)::getUserHandle(auth()->user()),
            );

            session()->put(
                MfaSession::AttestationPublicKey->value,
                serialize($publicKey),
            );

            $timebox->returnEarly();

            return $publicKey;
        }, microseconds: 300 * 1000);

        return response()->json($publicKey->jsonSerialize());
    }
}
