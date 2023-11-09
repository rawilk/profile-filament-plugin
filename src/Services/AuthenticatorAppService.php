<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Contracts\AuthenticatorAppService as AuthenticatorAppServiceContract;

class AuthenticatorAppService implements AuthenticatorAppServiceContract
{
    public function __construct(protected Google2FA $engine)
    {
    }

    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    public function qrCodeUrl(string $companyName, string $companyEmail, string $secret): string
    {
        return $this->engine->getQRCodeUrl($companyName, $companyEmail, $secret);
    }

    public function qrCodeSvg(string $url): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(150, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd,
            )
        ))->writeString($url);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    public function verify(string $secret, string $code): bool
    {
        return (bool) $this->engine->verifyKey($secret, $code);
    }
}
