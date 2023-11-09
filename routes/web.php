<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Http\Controllers\PasskeysController;
use Rawilk\ProfileFilament\Http\Controllers\WebauthnPublicKeysController;

Route::name('filament.')
    ->group(function () {
        foreach (Filament::getPanels() as $panel) {
            /** @var \Filament\Panel $panel */
            $panelId = $panel->getId();
            $domains = $panel->getDomains();

            if (! $panel->hasPlugin('rawilk/filament-profile')) {
                continue;
            }

            foreach ((empty($domains) ? [null] : $domains) as $domain) {
                Route::domain($domain)
                    ->middleware($panel->getMiddleware())
                    ->name("{$panelId}.")
                    ->prefix($panel->getPath())
                    ->group(function () use ($panel) {
                        $plugin = filament('rawilk/filament-profile');

                        if ($plugin->panelFeatures()->hasTwoFactorAuthentication()) {
                            // Two Factor Challenge
                            Route::get('/sessions/two-factor-challenge', $plugin->getMfaChallengeAction())->name('auth.mfa-challenge');

                            // Print Recovery Codes
                            Route::view('/recovery-codes/print', 'profile-filament::pages.print-recovery-codes')
                                ->name('auth.mfa.recovery-codes.print')
                                ->middleware($panel->getAuthMiddleware());
                        }

                        if ($plugin->panelFeatures()->hasSudoMode()) {
                            Route::get('/sessions/sudo', $plugin->getSudoChallengeAction())
                                ->name('auth.sudo-challenge')
                                ->middleware($panel->getAuthMiddleware());
                        }
                    });
            }
        }
    });

// Routes for webauthn public key generation...
Route::as('profile-filament::')
    ->middleware(['web'])
    ->group(function () {
        Route::post('/sessions/webauthn/assertion-pk/{user}', [WebauthnPublicKeysController::class, 'assertionPublicKey'])
            ->name('webauthn.assertion_pk')
            ->middleware(['signed']);

        Route::post('/sessions/webauthn/attestation-pk', [WebauthnPublicKeysController::class, 'attestationPublicKey'])
            ->name('webauthn.attestation_pk')
            ->middleware(['auth']);

        Route::post('/sessions/webauthn/passkey-assertion-pk', [PasskeysController::class, 'assertionPublicKey'])
            ->name('webauthn.passkey_assertion_pk')
            ->middleware(['signed']);

        Route::post('/sessions/webauthn/passkey-attestation-pk', [PasskeysController::class, 'attestationPublicKey'])
            ->name('webauthn.passkey_attestation_pk')
            ->middleware(['auth']);
    });
