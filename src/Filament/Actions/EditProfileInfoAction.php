<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Facades\Filament;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Rawilk\ProfileFilament\Events\Profile\ProfileInformationUpdated;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\ProfileNameInput;

class EditProfileInfoAction extends Action
{
    use CanCustomizeProcess;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::pages/profile.info.actions.edit.trigger'));

        $this->color('primary');

        $this->size(Size::Small);

        $this->modalHeading(__('profile-filament::pages/profile.info.actions.edit.modal_title'));

        $this->modalSubmitActionLabel(__('profile-filament::pages/profile.info.actions.edit.submit'));

        $this->modalWidth(Width::ExtraLarge);

        $this->successNotificationTitle(__('profile-filament::pages/profile.info.actions.edit.success'));

        $this->schema([
            ProfileNameInput::make(),
        ]);

        $this->fillForm(fn () => Filament::auth()->user()->toArray());

        $this->action(function () {
            $result = $this->process(function (array $data) {
                Filament::auth()->user()->forceFill($data)->save();

                return true;
            });

            if (! $result) {
                return;
            }

            ProfileInformationUpdated::dispatch(Filament::auth()->user());

            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'edit';
    }
}
