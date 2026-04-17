<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Emails;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Rawilk\ProfileFilament\Filament\Actions\Emails\CancelPendingEmailChangeAction;
use Rawilk\ProfileFilament\Filament\Actions\Emails\EditEmailAction;
use Rawilk\ProfileFilament\Filament\Actions\Emails\ResendPendingEmailAction;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\SudoChallengeAction;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Security;
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\UserEmailInfolist;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Rawilk\ProfileFilament\Models\PendingUserEmail;

/**
 * @property-read null|string $securityUrl
 * @property-read User $user
 * @property-read null|PendingUserEmail $pendingEmail
 */
class UserEmail extends ProfileComponent implements HasInfolists
{
    use InteractsWithInfolists;

    #[Computed]
    public function user(): User
    {
        return Filament::auth()->user();
    }

    #[Computed]
    public function securityUrl(): ?string
    {
        if (! $this->profilePlugin->hasSecurityPage()) {
            return null;
        }

        return $this->profilePlugin->getPageUrl(Security::class);
    }

    #[Computed]
    public function pendingEmail(): ?Model
    {
        if (! Filament::hasEmailChangeVerification()) {
            return null;
        }

        return app(config('profile-filament.models.pending_user_email'))::query()
            ->forUser($this->user)
            ->latest()
            ->first(['id', 'email']);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->infolist }}

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    #[On('email-updated')]
    #[On('email-cancelled')]
    public function onPendingEmailUpdated(): void
    {
        unset($this->pendingEmail);
    }

    public function editEmailAction(): Action
    {
        return EditEmailAction::make()->record(fn () => Filament::auth()->user());
    }

    public function cancelPendingEmailChangeSudoAction(): Action
    {
        return SudoChallengeAction::make('cancelPendingEmailChangeSudo')
            ->nextAction($this->cancelPendingEmailChangeAction());
    }

    public function cancelPendingEmailChangeAction(): Action
    {
        return CancelPendingEmailChangeAction::make();
    }

    public function resendPendingEmailAction(): Action
    {
        return ResendPendingEmailAction::make()
            ->record(fn () => $this->pendingEmail);
    }

    public function infolist(Schema $schema): Schema
    {
        return UserEmailInfolist::configure(
            schema: $schema,
            record: $this->user,
            editEmailAction: $this->editEmailAction(),
            cancelPendingEmailChangeAction: $this->cancelPendingEmailChangeSudoAction(),
            resendPendingEmailAction: $this->resendPendingEmailAction(),
            pendingEmail: $this->pendingEmail,
            securityUrl: $this->securityUrl,
        );
    }
}
