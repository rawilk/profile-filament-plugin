<x-mail::message>
# {{ __('profile-filament::mail.pending_email_verification.greeting') }}

{{ __('profile-filament::mail.pending_email_verification.line1', ['email' => $email]) }}

<x-mail::button :url="$url">
{{ __('profile-filament::mail.pending_email_verification.button') }}
</x-mail::button>

{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::mail.pending_email_verification.line2', ['minutes' => config('auth.verification.expire', 60)])) }}

{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::mail.pending_email_verification.line3')) }}

{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::mail.pending_email_verification.salutation', ['app_name' => config('app.name')])) }}
</x-mail::message>
