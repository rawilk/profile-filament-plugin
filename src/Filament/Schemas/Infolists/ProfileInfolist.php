<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Infolists;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Rawilk\ProfileFilament\Concerns\HasSimpleClosureEval;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Filament\Actions\EditProfileInfoAction;

class ProfileInfolist
{
    use Concerns\IsConfigurable;
    use HasSimpleClosureEval;

    public static function configure(Schema $schema, Model|Authenticatable $record): Schema
    {
        $schema
            ->record($record)
            ->components(static::resolveComponents($record));

        if (static::$configureSchemaUsing) {
            static::evaluate(static::$configureSchemaUsing, [
                'schema' => $schema,
                'record' => $record,
            ]);
        }

        return $schema;
    }

    public static function createdAtComponent(): Component
    {
        return TextEntry::make('created_at')
            ->label(__('profile-filament::pages/profile.info.created_at.label'))
            ->dateTime(
                format: 'F j, Y',
                timezone: ProfileFilament::userTimezone(),
            );
    }

    public static function nameComponent(): Component
    {
        return TextEntry::make('name')
            ->label(__('profile-filament::pages/profile.info.name.label'));
    }

    protected static function resolveComponents(Model|Authenticatable $user): array
    {
        if (static::$getComponentsUsing) {
            return static::evaluate(static::$getComponentsUsing, [
                'record' => $user,
                'user' => $user,
            ]);
        }

        return [
            Section::make(__('Profile Information'))
                ->headerActions([
                    EditProfileInfoAction::make(),
                ])
                ->schema([
                    static::nameComponent(),
                    static::createdAtComponent(),
                ]),
        ];
    }
}
