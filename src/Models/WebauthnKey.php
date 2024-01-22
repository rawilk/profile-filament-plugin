<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Models;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Rawilk\ProfileFilament\Exceptions\Webauthn\WrongUserHandle;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Webauthn\PublicKeyCredentialSource;

use function Rawilk\ProfileFilament\wrapDateInTimeTag;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $credential_id
 * @property array $public_key
 * @property null|string $attachment_type
 * @property bool $is_passkey
 * @property array $transports
 * @property null|\Illuminate\Support\Carbon $last_used_at
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property-read \Webauthn\PublicKeyCredentialSource $public_key_credential_source
 * @property-read \Illuminate\Support\HtmlString $last_used
 * @property-read \Illuminate\Support\HtmlString $registered_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Rawilk\ProfileFilament\Models\WebauthnKey byCredentialId(string $id)
 * @method static \Illuminate\Database\Eloquent\Builder|\Rawilk\ProfileFilament\Models\WebauthnKey notPasskeys()
 * @method static \Illuminate\Database\Eloquent\Builder|\Rawilk\ProfileFilament\Models\WebauthnKey passkeys()
 */
class WebauthnKey extends Model
{
    use HasFactory;

    protected $casts = [
        'public_key' => 'encrypted:json',
        'transports' => 'array',
        'is_passkey' => 'boolean',
        'last_used_at' => 'immutable_datetime',
    ];

    protected $hidden = [
        'public_key',
        'credential_id',
    ];

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('profile-filament.table_names.webauthn_key');
    }

    public static function fromPublicKeyCredentialSource(
        PublicKeyCredentialSource $source,
        User $user,
        string $keyName,
        ?string $attachmentType = null,
    ): WebauthnKey {
        throw_unless(
            static::getUserHandle($user) === $source->userHandle,
            WrongUserHandle::class,
        );

        $data = [
            'name' => $keyName,
            'user_id' => $user->getAuthIdentifier(),
            'attachment_type' => $attachmentType,
        ];

        return tap(static::make($data), function (self $webauthnKey) use ($source) {
            $webauthnKey->transports = $source->transports;
            $webauthnKey->credential_id = $source->publicKeyCredentialId;
            $webauthnKey->public_key = $source->jsonSerialize();
        });
    }

    public static function getUsername(User $user): string
    {
        /** @phpstan-ignore-next-line */
        return $user->email;
    }

    public static function getUserHandle(User $user): string
    {
        return (string) $user->getAuthIdentifier();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function canUpgradeToPasskey(): bool
    {
        return $this->attachment_type === 'platform' &&
            ! $this->is_passkey;
    }

    public function scopeByCredentialId(Builder $query, string $credentialId): void
    {
        $query->where('credential_id', Base64UrlSafe::encodeUnpadded($credentialId));
    }

    public function scopePasskeys(Builder $query): void
    {
        $query->where('is_passkey', true);
    }

    public function scopeNotPasskeys(Builder $query): void
    {
        $query->where('is_passkey', false);
    }

    public function lastUsed(): Attribute
    {
        return Attribute::make(
            get: function () {
                $date = blank($this->last_used_at)
                    ? __('profile-filament::pages/security.mfa.method_never_used')
                    : wrapDateInTimeTag($this->last_used_at->tz(ProfileFilament::userTimezone()), 'M d, Y g:i a');

                $translation = __('profile-filament::pages/security.mfa.method_last_used_date', ['date' => $date]);

                return new HtmlString(Str::inlineMarkdown($translation));
            },
        )->shouldCache();
    }

    protected function credentialId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Base64UrlSafe::decodeNoPadding($value),
            set: fn ($value) => Base64UrlSafe::encodeUnpadded($value),
        )->shouldCache();
    }

    protected function publicKeyCredentialSource(): Attribute
    {
        return Attribute::make(
            get: fn (): PublicKeyCredentialSource => PublicKeyCredentialSource::createFromArray($this->public_key),
        )->shouldCache();
    }

    protected function registeredAt(): Attribute
    {
        return Attribute::make(
            get: function () {
                $date = $this->created_at->tz(ProfileFilament::userTimezone());

                $translation = __('profile-filament::pages/security.mfa.method_registration_date', ['date' => wrapDateInTimeTag($date)]);

                return new HtmlString(Str::inlineMarkdown($translation));
            },
        )->shouldCache();
    }
}
