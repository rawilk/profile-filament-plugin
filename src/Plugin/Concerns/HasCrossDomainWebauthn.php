<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages\CrossDomainSecurityKeyRegistration;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages\CrossDomainWebauthnAuth;
use Rawilk\ProfileFilament\Support\Config;

trait HasCrossDomainWebauthn
{
    protected bool $registerCrossDomainWebauthnRoutes = true;

    protected string $crossDomainWebauthnRegistrationSlug = 'auth/webauthn-domain-registration';

    protected string $crossDomainWebauthnAuthSlug = 'auth/webauthn-domain-authentication';

    /** @var string|Closure|array<class-string, string>|null */
    protected string|Closure|array|null $crossDomainWebauthnRegisterRouteAction = CrossDomainSecurityKeyRegistration::class;

    /** @var string|Closure|array<class-string, string>|null */
    protected string|Closure|array|null $crossDomainWebauthnAuthRouteAction = CrossDomainWebauthnAuth::class;

    public function crossDomainWebauthn(
        string $registerRouteAction = CrossDomainSecurityKeyRegistration::class,
        string $authRouteAction = CrossDomainWebauthnAuth::class,
        bool $registerRoutes = true,
    ): static {
        $this->crossDomainWebauthnRegisterRouteAction = $registerRouteAction;
        $this->crossDomainWebauthnAuthRouteAction = $authRouteAction;
        $this->registerCrossDomainWebauthnRoutes = $registerRoutes;

        return $this;
    }

    public function useCrossDomainWebauthnRegistrationSlug(string $slug): static
    {
        $this->crossDomainWebauthnRegistrationSlug = $slug;

        return $this;
    }

    public function useCrossDomainWebauthnAuthSlug(string $slug): static
    {
        $this->crossDomainWebauthnAuthSlug = $slug;

        return $this;
    }

    public function needsCrossDomainWebauthn(?string $host = null): bool
    {
        // Don't handle cross-domain if we don't have the routes registered.
        if (! $this->shouldRegisterCrossDomainWebauthnRoutes()) {
            return false;
        }

        $host ??= config('app.url');

        $relyingPartyHost = Config::getRelyingPartyId();

        $appHost = parse_url(
            $host,
            PHP_URL_HOST,
        ) ?? $host;

        if ($appHost === $relyingPartyHost) {
            return false;
        }

        // Check if the app host is a subdomain of the relying party host.
        if (str_ends_with($appHost, ".{$relyingPartyHost}")) {
            return false;
        }

        return true;
    }

    public function shouldRegisterCrossDomainWebauthnRoutes(): bool
    {
        return $this->registerCrossDomainWebauthnRoutes;
    }

    public function getCrossDomainWebauthnRegistrationSlug(): string
    {
        return $this->crossDomainWebauthnRegistrationSlug;
    }

    public function getCrossDomainWebauthnAuthSlug(): string
    {
        return $this->crossDomainWebauthnAuthSlug;
    }

    /**
     * @return string|Closure|array<class-string, string>|null
     */
    public function getCrossDomainWebauthnRegisterRouteAction(): string|Closure|array|null
    {
        return $this->crossDomainWebauthnRegisterRouteAction;
    }

    /**
     * @return string|Closure|array<class-string, string>|null
     */
    public function getCrossDomainWebauthnAuthRouteAction(): string|Closure|array|null
    {
        return $this->crossDomainWebauthnAuthRouteAction;
    }

    public function getCrossDomainWebauthnRegistrationUrl(
        Authenticatable $user,
        string $originHost,
        array $data = [],
    ): string {
        return $this->signedUrlOnDifferentHost(
            hostName: Config::getRelyingPartyId(),
            routeName: Filament::getCurrentOrDefaultPanel()->generateRouteName('auth.webauthn.cross-domain-registration'),
            data: [
                'user' => Crypt::encrypt($user->getAuthIdentifier()),
                'origin' => $originHost,
                ...$data,
            ],
        );
    }

    public function getCrossDomainWebauthnAuthenticationUrl(
        ?Authenticatable $user,
        string $originalHost,
        array $data = [],
    ): string {
        return $this->signedUrlOnDifferentHost(
            hostName: Config::getRelyingPartyId(),
            routeName: Filament::getCurrentOrDefaultPanel()->generateRouteName('auth.webauthn.cross-domain-authentication'),
            data: [
                'user' => $user ? Crypt::encrypt($user->getAuthIdentifier()) : Str::random(),
                'origin' => $originalHost,
                ...$data,
            ],
        );
    }

    protected function signedUrlOnDifferentHost(string $hostName, string $routeName, array $data): string
    {
        /** @var \Illuminate\Routing\UrlGenerator $generator */
        $generator = app('url');

        // Capture the original origin (forcedRoot) to revert it later.
        $originalOrigin = (fn () => $this->forcedRoot)->call($generator);

        try {
            URL::useOrigin("https://{$hostName}");

            return URL::signedRoute($routeName, $data);
        } finally {
            // Revert back to the original origin.
            URL::useOrigin($originalOrigin);
        }
    }
}
