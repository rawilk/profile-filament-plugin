<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Actions;

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
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class DeleteSecurityKeyAction extends DeleteAction
{
    use RequiresSudoChallenge;

    protected null|Closure|WebauthnProvider $provider = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge();

        $this->icon(FilamentIcon::resolve(ActionsIconAlias::DELETE_ACTION) ?? Heroicon::OutlinedTrash);

        $this->size(Size::Small);

        $this->hiddenLabel();

        $this->outlined();

        $this->label(__('profile-filament::auth/multi-factor/webauthn/actions/delete-key.label'));

        $this->tooltip(
            fn (WebauthnKey $record) => __('profile-filament::auth/multi-factor/webauthn/actions/delete-key.tooltip', ['name' => $record->name])
        );

        $this->authorize('delete');

        $this->modalHeading(__('profile-filament::auth/multi-factor/webauthn/actions/delete-key.modal.heading'));
        $this->modalDescription(
            fn (WebauthnKey $record): Htmlable => str(__('profile-filament::auth/multi-factor/webauthn/actions/delete-key.modal.description', ['name' => e($record->name)]))->inlineMarkdown()->toHtmlString()
        );

        $this->successNotificationTitle(__('profile-filament::auth/multi-factor/webauthn/actions/delete-key.notifications.deleted.title'));

        $this->using(function (WebauthnKey $record) {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }

            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn $user */
            $user = Filament::auth()->user();
            $record->setRelation('user', $user);

            DB::transaction(function () use ($record) {
                $this->getProvider()->deleteSecurityKey($record);
            });

            return true;
        });
    }

    public function provider(Closure|WebauthnProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): WebauthnProvider
    {
        $provider = $this->evaluate($this->provider);

        if (! ($provider instanceof WebauthnProvider)) {
            throw new LogicException('An instance of [' . WebauthnProvider::class . '] is required for the delete security key action.');
        }

        return $provider;
    }
}
