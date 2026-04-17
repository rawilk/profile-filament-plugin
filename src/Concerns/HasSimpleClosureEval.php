<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns;

use Closure;
use ReflectionFunction;

trait HasSimpleClosureEval
{
    protected static function evaluate(mixed $value, array $namedInjections = []): mixed
    {
        if (! $value instanceof Closure) {
            return $value;
        }

        $reflection = new ReflectionFunction($value);
        $dependencies = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $namedInjections)) {
                $dependencies[] = $namedInjections[$name];

                continue;
            }

            // Fallback for optional parameters
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();

                continue;
            }

            // Fallback for nullable/optional parameters
            if ($parameter->isOptional() || $parameter->allowsNull()) {
                $dependencies[] = null;

                continue;
            }

            throw new \RuntimeException("Unresolvable dependency [\${$name}] for closure.");
        }

        return $value(...$dependencies);
    }
}
