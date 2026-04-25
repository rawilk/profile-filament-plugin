<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Filament\Actions;

use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\View\ActionsIconAlias;
use Filament\Facades\Filament;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class DeleteAuthenticatorAppAction extends DeleteAction
{
    use RequiresSudoChallenge;

    protected null|Closure|AppAuthenticationProvider $provider = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge();

        $this->icon(FilamentIcon::resolve(ActionsIconAlias::DELETE_ACTION) ?? Heroicon::OutlinedTrash);

        $this->size(Size::Small);

        $this->hiddenLabel();

        $this->outlined();

        $this->label(__('profile-filament::auth/multi-factor/app/actions/delete-app.label'));

        $this->tooltip(
            fn (AuthenticatorApp $record) => __('profile-filament::auth/multi-factor/app/actions/delete-app.tooltip', ['name' => $record->name])
        );

        $this->authorize('delete');

        $this->modalHeading(__('profile-filament::auth/multi-factor/app/actions/delete-app.modal.heading'));
        $this->modalDescription(
            fn (AuthenticatorApp $record): Htmlable => str(__('profile-filament::auth/multi-factor/app/actions/delete-app.modal.content', ['name' => e($record->name)]))->inlineMarkdown()->toHtmlString()
        );

        $this->successNotificationTitle(__('profile-filament::auth/multi-factor/app/actions/delete-app.notifications.deleted.title'));

        $this->using(function (AuthenticatorApp $record) {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }

            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication $user */
            $user = Filament::auth()->user();
            $record->setRelation('user', $user);

            DB::transaction(function () use ($record) {
                $this->getProvider()->deleteApp($record);
            });

            return true;
        });
    }

    public function provider(Closure|AppAuthenticationProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): AppAuthenticationProvider
    {
        $provider = $this->evaluate($this->provider);

        if (! ($provider instanceof AppAuthenticationProvider)) {
            throw new LogicException('An instance of [' . AppAuthenticationProvider::class . '] is required for the delete authenticator app action.');
        }

        return $provider;
    }
}
