<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Infolists;

use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Rawilk\ProfileFilament\Concerns\HasSimpleClosureEval;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\SudoChallengeAction;

class DeleteAccountInfolist
{
    use Concerns\IsConfigurable;
    use HasSimpleClosureEval;

    public static function configure(
        Schema $schema,
        Model|Authenticatable $record,
        Action $deleteAction,
    ): Schema {
        $schema
            ->record($record)
            ->components(static::resolveComponents($record, $deleteAction));

        if (static::$configureSchemaUsing) {
            static::evaluate(static::$configureSchemaUsing, [
                'record' => $record,
                'deleteAction' => $deleteAction,
            ]);
        }

        return $schema;
    }

    protected static function resolveComponents(Model|Authenticatable $record, Action $deleteAction): array
    {
        if (static::$getComponentsUsing) {
            return static::evaluate(static::$getComponentsUsing, [
                'user' => $record,
                'record' => $record,
                'deleteAction' => $deleteAction,
            ]);
        }

        return [
            Section::make(__('profile-filament::pages/settings.delete_account.title'))
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->iconColor('danger')
                ->schema([
                    Text::make(__('profile-filament::pages/settings.delete_account.description')),

                    SudoChallengeAction::make('delete-sudo-challenge')
                        ->nextAction($deleteAction),
                ]),
        ];
    }
}
