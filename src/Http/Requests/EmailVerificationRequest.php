<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Requests;

use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseRequest;

class EmailVerificationRequest extends BaseRequest
{
    public function authorize(): bool
    {
        if (! hash_equals((string) $this->user()->getRouteKey(), (string) $this->route('id'))) {
            return false;
        }

        if (! hash_equals(hash('sha3-256', $this->user()->getEmailForVerification()), (string) $this->route('hash'))) {
            return false;
        }

        return true;
    }
}
