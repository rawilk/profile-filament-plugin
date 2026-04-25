<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthenticateUsingPasskeyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'passkeyResponse' => ['required', 'json'],
        ];
    }
}
