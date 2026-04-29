<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Http\Controllers\BlockEmailChangeVerificationController;
use Rawilk\ProfileFilament\Http\Controllers\EmailChangeVerificationController;
use Rawilk\ProfileFilament\Http\Controllers\EmailVerificationController;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

Route::name('filament.')
    ->group(function (): void {
        foreach (Filament::getPanels() as $panel) {
            /** @var \Filament\Panel $panel */
            if (! $panel->hasPlugin(ProfileFilamentPlugin::PLUGIN_ID)) {
                continue;
            }

            $panelId = $panel->getId();
            $domains = $panel->getDomains();

            foreach ((empty($domains) ? [null] : $domains) as $domain) {
                Route::domain($domain)
                    ->middleware($panel->getMiddleware())
                    ->name("{$panelId}." . ((filled($domain) && (count($domains) > 1)) ? "{$domain}." : ''))
                    ->prefix($panel->getPath())
                    ->group(function () use ($panel) {
                        /** @var ProfileFilamentPlugin $plugin */
                        $plugin = $panel->getPlugin(ProfileFilamentPlugin::PLUGIN_ID);

                        Route::middleware($panel->getAuthMiddleware())
                            ->group(function () use ($panel, $plugin): void {
                                // We are overriding Filament's email verification routes to apply our own logic instead.
                                if ($panel->hasEmailVerification()) {
                                    Route::name('auth.email-verification.')
                                        ->prefix($panel->getEmailVerificationRoutePrefix())
                                        ->group(function () use ($panel, $plugin): void {
                                            if (filled($promptAction = $plugin->getEmailVerificationPromptRouteAction())) {
                                                Route::get($panel->getEmailVerificationPromptRouteSlug(), $promptAction)
                                                    ->name('prompt');
                                            }

                                            Route::get($panel->getEmailVerificationRouteSlug('/{id}/{hash}'), EmailVerificationController::class)
                                                ->middleware(['signed', 'throttle:6,1'])
                                                ->name('verify');
                                        });
                                }

                                // We are overriding Filament's email change routes to apply our own logic instead.
                                if ($panel->hasEmailChangeVerification()) {
                                    Route::name('auth.email-change-verification.')
                                        ->prefix($panel->getEmailChangeVerificationRoutePrefix())
                                        ->group(function () use ($panel): void {
                                            Route::get($panel->getEmailChangeVerificationRouteSlug('/{id}/{email}'), EmailChangeVerificationController::class)
                                                ->middleware(['signed', 'throttle:6,1'])
                                                ->name('verify');

                                            Route::get($panel->getEmailChangeVerificationRouteSlug('/{id}/{email}/block'), BlockEmailChangeVerificationController::class)
                                                ->middleware(['signed', 'throttle:6,1'])
                                                ->name('block-verification');
                                        });
                                }
                            });

                        // MultiFactor Challenge
                        if ($plugin->hasMultiFactorAuthentication()) {
                            Route::get($plugin->getMultiFactorAuthenticationRouteSlug(), $plugin->getMultiFactorAuthenticationRouteAction())
                                ->name('auth.multi-factor-challenge');

                            // We are overriding Filament's prompt to apply our own logic.
                            if ($plugin->isMultiFactorAuthenticationRequired()) {
                                Route::name('auth.multi-factor-authentication.')
                                    ->prefix($panel->getMultiFactorAuthenticationRoutePrefix())
                                    ->group(function () use ($panel, $plugin): void {
                                        Route::get($panel->getSetUpRequiredMultiFactorAuthenticationRouteSlug(), $plugin->getSetUpRequiredMultiFactorAuthenticationRouteAction())
                                            ->name('set-up-required');
                                    });
                            }
                        }

                        // Sudo Challenge
                        if ($plugin->hasSudoMode()) {
                            Route::get($plugin->getSudoChallengeRouteSlug(), $plugin->getSudoChallengeRouteAction())
                                ->name('auth.sudo-challenge')
                                ->middleware($panel->getAuthMiddleware());
                        }
                    });
            }
        }
    });
