<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Sudo\Concerns;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Timebox;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Dto\SudoChallengeAssertions\SudoChallengeAssertion;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Support\SudoChallengeProviders\Factory as SudoChallengeModeFactory;

/**
 * @property-read Collection $challengeOptions All available challenge providers for the authenticated user
 * @property-read string $challengeProvider The classname of the current challenge provider
 * @property-read \Filament\Schemas\Components\Form $form
 * @property-read Authenticatable $user
 */
trait HasSudoChallengeForm
{
    public ?array $data = [];

    /**
     * The slug of the current sudo challenge provider.
     */
    #[Locked]
    public ?string $selectedProvider = null;

    #[Locked]
    public ?SudoChallengeAssertion $challengeAssertion = null;

    #[Computed]
    public function user(): Authenticatable
    {
        return Filament::auth()->user();
    }

    #[Computed]
    public function challengeOptions(): Collection
    {
        return app(SudoChallengeModeFactory::class)($this->user);
    }

    #[Computed]
    public function challengeProvider(): ?string
    {
        return $this->challengeOptions->firstWhere(
            fn (string $challengeMode): bool => $challengeMode::slug() === $this->selectedProvider,
        );
    }

    public function mountHasSudoChallengeForm(): void
    {
        $this->form->fill();
    }

    public function confirm(Request $request, ?array $extra = null): void
    {
        if (! $this->challengeProvider) {
            return;
        }

        App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($request, $extra) {
            $this->challengeAssertion = $this->challengeProvider::assert(
                data: $this->form->getState(),
                user: $this->user,
                request: $request,
                extra: $extra,
            );

            if ($this->challengeAssertion->isValid()) {
                $timebox->returnEarly();
            }
        }, microseconds: 300 * 1000);

        if (! $this->challengeAssertion->isValid()) {
            return;
        }

        Sudo::activate();
        SudoModeActivated::dispatch($this->user, $request);

        $this->dispatch('sudo-confirmed');
    }

    public function submitAction(): Action
    {
        return Action::make('submit')
            ->action('confirm')
            ->color('primary')
            ->label(
                fn () => $this->challengeProvider
                    ? $this->challengeProvider::submitLabel($this->user)
                    : __('profile-filament::messages.sudo_challenge.password.submit'),
            )
            ->hidden(
                fn () => $this->challengeProvider
                    ? $this->challengeProvider::submitIsHidden($this->user)
                    : false,
            )
            ->extraAttributes(['class' => 'w-full']);
    }
}
