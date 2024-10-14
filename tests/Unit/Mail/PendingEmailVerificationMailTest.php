<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Mail\PendingEmailVerificationMail;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

it('can be sent', function () {
    $this->freezeSecond();

    $pendingEmail = PendingUserEmail::factory()->for(User::factory())->create([
        'email' => 'email@example.test',
    ]);

    $mailable = new PendingEmailVerificationMail(
        pendingUserEmail: $pendingEmail,
        panelId: 'admin',
    );

    $mailable
        ->assertHasSubject(__('profile-filament::mail.pending_email_verification.subject'))
        ->assertSeeInHtml('email@example.test')
        ->assertSeeInHtml($pendingEmail->verification_url);
});
