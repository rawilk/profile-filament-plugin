<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Support;

use Closure;
use Detection\Cache\CacheException;
use Detection\Exception\MobileDetectException;
use Detection\MobileDetect;

/**
 * @copyright Originally created by Jens Segers: https://github.com/jenssegers/agent
 * @copyright Originally created by Laravel Jetstream: https://github.com/laravel/jetstream
 */
class Agent extends MobileDetect
{
    /**
     * @var array<string, string>
     */
    protected static array $additionalOperatingSystems = [
        'Windows' => 'Windows',
        'Windows NT' => 'Windows NT',
        'OS X' => 'Mac OS X',
        'Debian' => 'Debian',
        'Ubuntu' => 'Ubuntu',
        'Macintosh' => 'PPC',
        'OpenBSD' => 'OpenBSD',
        'Linux' => 'Linux',
        'ChromeOS' => 'CrOS',
    ];

    /**
     * @var array<string, string>
     */
    protected static array $additionalBrowsers = [
        'Opera Mini' => 'Opera Mini',
        'Opera' => 'Opera|OPR',
        'Edge' => 'Edge|Edg',
        'Coc Coc' => 'coc_coc_browser',
        'UCBrowser' => 'UCBrowser',
        'Vivaldi' => 'Vivaldi',
        'Chrome' => 'Chrome',
        'Firefox' => 'Firefox',
        'Safari' => 'Safari',
        'IE' => 'MSIE|IEMobile|MSIEMobile|Trident/[.0-9]+',
        'Netscape' => 'Netscape',
        'Mozilla' => 'Mozilla',
        'WeChat' => 'MicroMessenger',
    ];

    public function platform()
    {
        return $this->retrieveUsingCacheOrResolve('profile-filament.platform', function () {
            $platform = $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(MobileDetect::getOperatingSystems(), static::$additionalOperatingSystems)
            );

            if ($platform === 'OS X') {
                return 'Mac OS';
            }

            return $platform;
        });
    }

    public function browser()
    {
        return $this->retrieveUsingCacheOrResolve('profile-filament.browser', function () {
            return $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(static::$additionalBrowsers, MobileDetect::getBrowsers())
            );
        });
    }

    public function isDesktop()
    {
        return $this->retrieveUsingCacheOrResolve('profile-filament.desktop', function () {
            // Check specifically for cloudfront headers if the useragent === 'Amazon CloudFront'
            if (
                $this->getUserAgent() === (static::$cloudFrontUA ?? 'Amazon CloudFront')
                && $this->getHttpHeader('HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER') === 'true'
            ) {
                return true;
            }

            return ! $this->isMobile() && ! $this->isTablet();
        });
    }

    protected function findDetectionRulesAgainstUserAgent(array $rules)
    {
        $userAgent = $this->getUserAgent();

        foreach ($rules as $key => $regex) {
            if (empty($regex)) {
                continue;
            }

            if ($this->match($regex, $userAgent)) {
                return $key ?: reset($this->matchesArray);
            }
        }

        return null;
    }

    protected function retrieveUsingCacheOrResolve(string $key, Closure $callback)
    {
        try {
            $cacheKey = $this->createCacheKey($key);

            if (! is_null($cacheItem = $this->cache->get($cacheKey))) {
                return $cacheItem->get();
            }

            return tap(call_user_func($callback), function ($result) use ($cacheKey) {
                $this->cache->set($cacheKey, $result);
            });
        } catch (CacheException $e) {
            throw new MobileDetectException("Cache problem in for {$key}: {$e->getMessage()}");
        }
    }

    protected function mergeRules(...$all): array
    {
        $merged = [];

        foreach ($all as $rules) {
            foreach ($rules as $key => $value) {
                if (empty($merged[$key])) {
                    $merged[$key] = $value;
                } elseif (is_array($merged[$key])) {
                    $merged[$key][] = $value;
                } else {
                    $merged[$key] .= '|' . $value;
                }
            }
        }

        return $merged;
    }
}
