<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\CredentialRecordConverter;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\Serializer;
use Rawilk\ProfileFilament\Support\Config;
use Webauthn\PublicKeyCredentialSource;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $credential_id
 * @property PublicKeyCredentialSource $data
 * @property null|string $attachment_type
 * @property bool $is_passkey
 * @property null|\Illuminate\Support\Carbon $last_used_at
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\Rawilk\ProfileFilament\Models\WebauthnKey byCredentialId(string $rawId)
 * @method static \Illuminate\Database\Eloquent\Builder|\Rawilk\ProfileFilament\Models\WebauthnKey passkey(bool $condition = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\Rawilk\ProfileFilament\Models\WebauthnKey ownedBy(Model $user)
 */
class WebauthnKey extends Model
{
    use Concerns\HasAuthenticatorTimestamps;
    use HasFactory;

    protected $hidden = [
        'data',
        'credential_id',
    ];

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(Config::getTableName('webauthn_key'));
    }

    /**
     * Encode a credential id in a DB-safe way.
     */
    public static function encodeCredentialId(string $raw): string
    {
        return Base64UrlSafe::encodeUnpadded($raw);
    }

    public static function decodeCredentialId(string $id): string
    {
        return Base64UrlSafe::decodeNoPadding($id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Config::getAuthenticatableModel());
    }

    protected function data(): Attribute
    {
        $serializer = Serializer::make();

        return new Attribute(
            get: fn (string $value): PublicKeyCredentialSource => CredentialRecordConverter::toPublicKeyCredentialSource(
                $serializer->fromJson(
                    $value,
                    PublicKeyCredentialSource::class,
                ),
            ),
            set: fn (PublicKeyCredentialSource $value) => [
                'credential_id' => static::encodeCredentialId($value->publicKeyCredentialId),
                'data' => $serializer->toJson($value),
            ],
        );
    }

    #[Scope]
    protected function byCredentialId(Builder $query, string $rawId): void
    {
        $query->where('credential_id', static::encodeCredentialId($rawId));
    }

    #[Scope]
    protected function passkey(Builder $query, bool $condition = true): void
    {
        $query->where('is_passkey', $condition);
    }

    #[Scope]
    protected function ownedBy(Builder $query, Model $user): void
    {
        $query->whereBelongsTo($user, 'user');
    }

    protected function casts(): array
    {
        return [
            'is_passkey' => 'boolean',
            'last_used_at' => 'immutable_datetime',
        ];
    }
}
