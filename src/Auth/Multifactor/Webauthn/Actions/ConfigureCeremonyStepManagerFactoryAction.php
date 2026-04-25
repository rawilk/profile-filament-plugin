<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions;

use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

class ConfigureCeremonyStepManagerFactoryAction
{
    public function __invoke(): CeremonyStepManagerFactory
    {
        return new CeremonyStepManagerFactory;
    }
}
