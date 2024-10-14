<?php

declare(strict_types=1);

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

arch()->expect('Rawilk\ProfileFilament\Mail')
    ->classes()
    ->toExtend(Mailable::class);

arch()->expect('Rawilk\ProfileFilament\Mail')
    ->classes()
    ->toImplement(ShouldQueue::class)
    ->toUse([
        Queueable::class,
        SerializesModels::class,
    ]);

arch()->expect('Rawilk\ProfileFilament\Mail')
    ->classes()
    ->toHaveMethods([
        'content',
        'envelope',
    ]);

//arch()->expect('Rawilk\ProfileFilament')
//    ->classes()
//    ->not->toExtend(Mailable::class)->ignoring([
//        'Rawilk\ProfileFilament\Mail',
//    ]);
