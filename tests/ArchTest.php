<?php

declare(strict_types=1);

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Facade;
use Rawilk\ProfileFilament\Actions\Auth\PrepareUserSession;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

it('will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'ddd', 'var_dump'])
    ->each->not->toBeUsed();

/**
 * This test requires the classes(), traits(), etc. qualifiers
 * to be used because our helpers are namespaced, and without
 * those qualifiers, pest is trying to use file_get_contents()
 * on each function in the helpers file for some reason.
 */
test('strict types are used')
    ->expect('Rawilk\ProfileFilament')
    ->classes()->toUseStrictTypes()
    ->traits()->toUseStrictTypes()
    ->interfaces()->toUseStrictTypes()
    ->enums()->toUseStrictTypes();

test('tests are using strict types')
    ->expect('Rawilk\ProfileFilament\Tests')
    ->toUseStrictTypes();

test('actions are defined correctly')
    ->expect('Rawilk\ProfileFilament\Actions')
    ->toBeClasses()
    ->toHaveSuffix('Action')->ignoring([
        PrepareUserSession::class,
    ]);

test('traits are defined correctly')
    ->expect('Rawilk\ProfileFilament\Concerns')
    ->toBeTraits();

test('contracts are defined correctly')
    ->expect('Rawilk\ProfileFilament\Contracts')
    ->toBeInterfaces();

test('dto objects are defined correctly')
    ->expect('Rawilk\ProfileFilament\Dto')
    ->toBeClasses()
    ->toExtendNothing()
    ->not->toHaveSuffix('Dto');

test('enums are defined correctly')
    ->expect('Rawilk\ProfileFilament\Enums')
    ->toBeEnums();

test('events are defined correctly')
    ->expect('Rawilk\ProfileFilament\Events')
    ->toBeClasses()
    ->toBeFinal()->ignoring([
        ProfileFilamentEvent::class,
    ])
    ->not->toHaveSuffix('Event')->ignoring([
        ProfileFilamentEvent::class,
    ]);

test('facades are defined correctly')
    ->expect('Rawilk\ProfileFilament\Facades')
    ->toBeClasses()
    ->toExtend(Facade::class)
    ->not->toHaveSuffix('Facade');

test('controllers are defined correctly')
    ->expect('Rawilk\ProfileFilament\Http\Controllers')
    ->toBeClasses()
    ->toExtendNothing()
    ->not->toBeFinal()
    ->toHaveSuffix('Controller');

test('middleware is defined correctly')
    ->expect('Rawilk\ProfileFilament\Http\Middleware')
    ->toBeClasses()
    ->toExtendNothing()
    ->not->toHaveSuffix('Middleware')
    ->not->toBeFinal()
    ->toHaveMethod('handle');

test('mailables are defined correctly')
    ->expect('Rawilk\ProfileFilament\Mail')
    ->toBeClasses()
    ->toHaveSuffix('Mail')
    ->toExtend(Mailable::class)
    ->toImplement(ShouldQueue::class)
    ->toUse([
        Queueable::class,
        SerializesModels::class,
    ])
    ->not->toBeFinal()
    ->toHaveMethod('content')
    ->toHaveMethod('envelope');

test('models are defined correctly')
    ->expect('Rawilk\ProfileFilament\Models')
    ->toBeClasses()
    ->classes()
    ->toExtend(Model::class)
    ->not->toBeFinal();

test('policies are defined correctly')
    ->expect('Rawilk\ProfileFilament\Policies')
    ->toBeClasses()
    ->toExtendNothing()
    ->toUse(HandlesAuthorization::class)
    ->not->toBeFinal()
    ->toHaveSuffix('Policy');

test('providers are defined correctly')
    ->expect('Rawilk\ProfileFilament\Providers')
    ->toBeClasses()
    ->toBeFinal()
    ->toHaveSuffix('Provider');

test('responses are defined correctly')
    ->expect('Rawilk\ProfileFilament\Responses')
    ->toBeClasses()
    ->toHaveSuffix('Response')
    ->not->toBeFinal();

test('services are defined correctly')
    ->expect('Rawilk\ProfileFilament\Services')
    ->toBeClasses();

test('support classes are defined correctly')
    ->expect('Rawilk\ProfileFilament\Support')
    ->toBeClasses();
