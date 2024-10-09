<div class="mt-4">
    <p>
        {{
            str(__('profile-filament::pages/security.mfa.app.form_intro', [
                'authy' => 'https://authy.com/guides/',
                'microsoft' => 'https://www.microsoft.com/en-us/account/authenticator/',
                'one_password' => 'https://support.1password.com/one-time-passwords/',
            ]))
                ->inlineMarkdown()
                ->toHtmlString()
        }}
    </p>

    <p class="mt-3 font-bold dark:text-white text-gray-600">{{ __('profile-filament::pages/security.mfa.app.scan_title') }}</p>

    <p class="mt-3">
        {{ str(__('profile-filament::pages/security.mfa.app.scan_instructions'))->inlineMarkdown()->toHtmlString() }}
    </p>

    @if ($qrCodeUrl)
        <div class="mt-5">
            <div class="p-0.5 inline-block rounded-md dark:bg-white">
                {{ str($this->authenticatorService->qrCodeSvg($qrCodeUrl))->toHtmlString() }}
            </div>
        </div>
    @endif

    <p class="mt-3">
       {{ str(__('profile-filament::pages/security.mfa.app.enter_code_instructions'))->inlineMarkdown()->toHtmlString() }}
    </p>
</div>
