<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Timebox;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Symfony\Component\HttpFoundation\Response;

class WebauthnPublicKeysController
{
    public function assertionPublicKey(int|string $user, Request $request): Response
    {
        $publicKey = App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($user, $request) {
            $user = $this->resolveUser($user);

            if (! $user) {
                return null;
            }

            $publicKey = Webauthn::assertionObjectFor($user);

            session()->put(
                $request->get('s') ?? MfaSession::AssertionPublicKey->value,
                $publicKey,
            );

            $timebox->returnEarly();

            return $publicKey;
        }, microseconds: 300 * 1000);

        if ($publicKey === null) {
            return $this->genericAssertionOptions(
                sessionKey: $request->get('s') ?? MfaSession::AssertionPublicKey->value,
            );
        }

        return response()->json(
            Webauthn::serializePublicKeyOptionsForRequest($publicKey)
        );
    }

    public function attestationPublicKey(): Response
    {
        $publicKey = App::make(Timebox::class)->call(callback: function (Timebox $timebox) {
            $publicKey = Webauthn::attestationObjectFor(auth()->user());

            session()->put(
                MfaSession::AttestationPublicKey->value,
                $publicKey,
            );

            $timebox->returnEarly();

            return $publicKey;
        }, microseconds: 300 * 1000);

        return response()->json(
            Webauthn::serializePublicKeyOptionsForRequest($publicKey)
        );
    }

    protected function genericAssertionOptions(string $sessionKey): Response
    {
        $publicKey = Webauthn::genericAssertion();

        session()->put(
            $sessionKey,
            $publicKey,
        );

        return response()->json(
            Webauthn::serializePublicKeyOptionsForRequest($publicKey)
        );
    }

    /**
     * This method is inspired by how Filament resolves model
     * records in resource pages.
     */
    protected function resolveUser(int|string $key): ?User
    {
        $model = app(config('auth.providers.users.model'));

        return $model
            ->resolveRouteBindingQuery($model::query(), $key)
            ->first();
    }
}
