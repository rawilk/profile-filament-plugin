<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Rawilk\ProfileFilament\Mail\PendingEmailVerifiedMail;
use Rawilk\ProfileFilament\Models\OldUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

it('can be sent', function () {
    $oldEmail = OldUserEmail::factory()->for(User::factory())->create([
        'email' => 'email@example.test',
    ]);

    Date::setTestNow('2023-01-01 10:00:00');

    $mailable = new PendingEmailVerifiedMail(
        newEmail: 'new-email@example.test',
        oldUserEmail: $oldEmail,
        panelId: 'admin',
        ip: '127.0.0.1',
        date: now(),
    );

    $mailable
        ->assertHasSubject(__('profile-filament::mail.email_updated.subject'))
        ->assertDontSeeInHtml('new-email@example.test')
        ->assertSeeInHtml('ne*******@ex*****.test')
        ->assertSeeInHtml($oldEmail->revert_url)
        ->assertSeeInHtml('Request details')
        ->assertSeeInHtml('127.0.0.1')
        ->assertSeeInHtml('Sun, 1 Jan 2023 10:00 AM (UTC +0000)');
});
