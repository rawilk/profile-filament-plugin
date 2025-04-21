<x-mail::message>
# {{ __('profile-filament::mail.email_updated.greeting') }}

{{ __('profile-filament::mail.email_updated.line1', ['app_name' => config('app.name')]) }}

{{ __('profile-filament::mail.email_updated.line2', ['email' => e($maskedEmail)]) }}

{{ __('profile-filament::mail.email_updated.line3') }}

{{ __('profile-filament::mail.email_updated.line4', ['url' => $url, 'days' => $linkExpirationDays]) }}

{{ str($requestDetails)->inlineMarkdown()->toHtmlString() }}

{{ str(__('profile-filament::mail.email_updated.salutation', ['app_name' => config('app.name')]))->inlineMarkdown()->toHtmlString() }}
</x-mail::message>
