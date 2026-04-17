<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Infolists;

use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Concerns\HasSimpleClosureEval;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\SudoChallengeAction;
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\Components\EmailTextEntry;
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\Components\PendingEmailChangeText;
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\Components\SecurityUrlHelpText;

class UserEmailInfolist
{
    use Concerns\IsConfigurable;
    use HasSimpleClosureEval;

    public static function configure(
        Schema $schema,
        Model|Authenticatable $record,
        Action $editEmailAction,
        Action $cancelPendingEmailChangeAction,
        Action $resendPendingEmailAction,
        ?Model $pendingEmail = null,
        ?string $securityUrl = null,
    ): Schema {
        $schema
            ->record($record)
            ->components(static::resolveComponents($record, $editEmailAction, $pendingEmail, $securityUrl, $cancelPendingEmailChangeAction, $resendPendingEmailAction));

        if (static::$configureSchemaUsing) {
            static::evaluate(static::$configureSchemaUsing, [
                'schema' => $schema,
                'pendingEmail' => $pendingEmail,
                'record' => $record,
                'securityUrl' => $securityUrl,
                'editEmailAction' => $editEmailAction,
                'cancelPendingEmailChangeAction' => $cancelPendingEmailChangeAction,
                'resendPendingEmailAction' => $resendPendingEmailAction,
            ]);
        }

        return $schema;
    }

    protected static function resolveComponents(
        Model|Authenticatable $user,
        Action $editEmailAction,
        ?Model $pendingEmail,
        ?string $securityUrl,
        Action $cancelPendingEmailChangeAction,
        Action $resendPendingEmailAction,
    ): array {
        if (static::$getComponentsUsing) {
            return static::evaluate(static::$getComponentsUsing, [
                'user' => $user,
                'record' => $user,
                'pendingEmail' => $pendingEmail,
                'securityUrl' => $securityUrl,
                'editEmailAction' => $editEmailAction,
                'cancelPendingEmailChangeAction' => $cancelPendingEmailChangeAction,
                'resendPendingEmailAction' => $resendPendingEmailAction,
            ]);
        }

        return [
            Section::make()
                ->key('email')
                ->heading(function () use ($pendingEmail): Htmlable {
                    return new HtmlString(Blade::render(<<<'HTML'
                    <span class="flex items-center gap-x-2">
                        <span>{{ __('profile-filament::pages/settings.email.heading') }}</span>

                        @if ($pendingEmail)
                            <x-filament::badge color="warning">
                                {{ __('profile-filament::pages/settings.email.change_pending_badge') }}
                            </x-filament::badge>
                        @endif
                    </span>
                    HTML, ['pendingEmail' => $pendingEmail]));
                })
                ->headerActions([
                    SudoChallengeAction::make('update-email-sudo')
                        ->nextAction($editEmailAction),
                ])
                ->schema([
                    PendingEmailChangeText::make(null)
                        ->usingEmail($pendingEmail)
                        ->withCancelAction($cancelPendingEmailChangeAction)
                        ->withResendAction($resendPendingEmailAction)
                        ->visible(fn (): bool => filled($pendingEmail?->getKey())),
                    EmailTextEntry::make('email')->inlineLabel(),
                    SecurityUrlHelpText::make(null)
                        ->usingUrl($securityUrl)
                        ->visible(fn (): bool => filled($securityUrl)),
                ]),
        ];
    }
}
