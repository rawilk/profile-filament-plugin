<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sessions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Rawilk\FilamentPasswordInput\Password;
use Throwable;

class RevokeSessionAction extends Action
{
    use CanCustomizeProcess;
    use Concerns\ManagesSessions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation();

        $this->label(__('profile-filament::pages/sessions.manager.actions.revoke.trigger'));

        $this->color('danger');

        $this->size(ActionSize::Small);

        $this->modalWidth(MaxWidth::Large);

        $this->modalSubmitActionLabel(__('profile-filament::pages/sessions.manager.actions.revoke.submit_button'));

        $this->successNotificationTitle(__('profile-filament::pages/sessions.manager.actions.revoke.success'));

        $this->modalDescription(null);

        $this->modalIcon('heroicon-o-signal');

        $this->form([
            $this->getPasswordInput(),
        ]);

        $this->action(function (array $arguments) {
            $result = $this->process(function (array $data, ?string $sessionId) {
                if (! $sessionId) {
                    return false;
                }

                if (! $this->isUsingDatabaseDriver()) {
                    return true;
                }

                /** @var \Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model $user */
                $user = Filament::auth()->user();

                $password = config('profile-filament.hash_user_passwords')
                    ? Hash::make($data['password'])
                    : $data['password'];

                $user->forceFill([
                    $user->getAuthPasswordName() => $password,
                ])->save();

                $this->rehashSession();

                $this->deleteById($sessionId);

                return true;
            }, [
                'sessionId' => rescue(
                    fn () => Crypt::decryptString(data_get($arguments, 'record')),
                    report: false,
                ),
            ]);

            if ($result === false) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'revokeSession';
    }

    protected function getPasswordInput(): Component
    {
        return Password::make('password')
            ->label(__('profile-filament::pages/sessions.manager.password_input_label'))
            ->helperText(__('profile-filament::pages/sessions.manager.password_input_helper'))
            ->currentPassword()
            ->required();
    }

    protected function deleteById(string $sessionId): void
    {
        defer(function () use ($sessionId) {
            $newPasswordHash = Filament::auth()->user()->getAuthPassword();
            $guard = $this->getGuard();

            $this->table()
                ->whereNotIn('id', [session()->getId(), $sessionId])
                ->select(['id', 'payload'])
                ->chunkById(100, function ($sessions) use ($newPasswordHash, $guard) {
                    foreach ($sessions as $session) {
                        try {
                            $payload = unserialize(base64_decode($session->payload));

                            $payload["password_hash_{$guard}"] = $newPasswordHash;

                            $this->table()
                                ->where('id', $session->id)
                                ->update([
                                    'payload' => base64_encode(serialize($payload)),
                                ]);
                        } catch (Throwable) {
                        }
                    }
                });
        });

        $this->table()
            ->where('id', $sessionId)
            ->delete();
    }
}
