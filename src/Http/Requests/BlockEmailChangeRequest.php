<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Requests;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class BlockEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! hash_equals((string) $this->user()->getRouteKey(), (string) $this->route('id'))) {
            return false;
        }

        if (blank($this->query('token'))) {
            return false;
        }

        try {
            return filled(Crypt::decryptString($this->route('email')));
        } catch (DecryptException) {
            return false;
        }
    }

    public function fulfill(): bool
    {
        $pendingEmail = app(config('profile-filament.models.pending_user_email'))
            ->whereToken($this->query('token'))
            ->with('user')
            ->first();

        if (! $pendingEmail?->user()->is($this->user())) {
            return false;
        }

        return $pendingEmail->delete();
    }
}
