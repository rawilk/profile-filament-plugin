<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Http\Controllers\RevertEmailController;
use Rawilk\ProfileFilament\Http\Controllers\VerifyPendingEmailController;
use Rawilk\ProfileFilament\Http\Middleware\RequiresSudoMode;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

Route::name('filament.')
    ->group(function () {
        foreach (Filament::getPanels() as $panel) {
            /** @var \Filament\Panel $panel */
            $panelId = $panel->getId();
            $domains = $panel->getDomains();

            if (! $panel->hasPlugin(ProfileFilamentPlugin::PLUGIN_ID)) {
                continue;
            }

            foreach ((empty($domains) ? [null] : $domains) as $domain) {
                Route::domain($domain)
                    ->middleware($panel->getMiddleware())
                    ->name("{$panelId}.")
                    ->prefix($panel->getPath())
                    ->group(function () use ($panel) {
                        $plugin = $panel->getPlugin(ProfileFilamentPlugin::PLUGIN_ID);

                        if ($plugin->panelFeatures()->hasTwoFactorAuthentication()) {
                            // Two Factor Challenge
                            Route::get('/sessions/two-factor-challenge', $plugin->getMfaChallengeAction())->name('auth.mfa.challenge');

                            // Print Recovery Codes
                            Route::view('/recovery-codes/print', 'profile-filament::pages.print-recovery-codes')
                                ->name('auth.mfa.recovery-codes.print')
                                ->middleware([
                                    ...$panel->getAuthMiddleware(),
                                    RequiresSudoMode::class,
                                ]);
                        }

                        if ($plugin->panelFeatures()->hasSudoMode()) {
                            Route::get('/sessions/sudo', $plugin->getSudoChallengeAction())
                                ->name('auth.sudo-challenge')
                                ->middleware($panel->getAuthMiddleware());
                        }

                        // Email verification
                        Route::get('/pending-emails/verify/{token}', VerifyPendingEmailController::class)
                            ->name('pending_email.verify')
                            ->middleware(['throttle:6,1', 'signed']);

                        Route::get('/pending-emails/revert/{token}', RevertEmailController::class)
                            ->name('pending_email.revert')
                            ->middleware(['throttle:6,1', 'signed']);
                    });
            }
        }
    });
