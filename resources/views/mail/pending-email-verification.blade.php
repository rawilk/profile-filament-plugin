<x-mail::message>
# {{ __('profile-filament::mail.pending_email_verification.greeting') }}

{{ __('profile-filament::mail.pending_email_verification.line1', ['email' => e($email)]) }}

<x-mail::button :url="$url">
{{ __('profile-filament::mail.pending_email_verification.button') }}
</x-mail::button>

{{ __('profile-filament::mail.pending_email_verification.line2', ['expire' => $linkExpires]) }}

{{ __('profile-filament::mail.pending_email_verification.line3') }}

{{ str(__('profile-filament::mail.pending_email_verification.salutation', ['app_name' => config('app.name')]))->inlineMarkdown()->toHtmlString() }}
</x-mail::message>
