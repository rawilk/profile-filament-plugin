<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\Fixtures\database\factories;

use Rawilk\ProfileFilament\Tests\Fixtures\Models\BasicUser;

final class BasicUserFactory extends UserFactory
{
    protected $model = BasicUser::class;
}
