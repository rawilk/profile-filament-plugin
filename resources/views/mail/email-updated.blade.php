<x-mail::message>
# {{ __('profile-filament::mail.email_updated.greeting') }}

{{ __('profile-filament::mail.email_updated.line1', ['app_name' => config('app.name')]) }}

{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::mail.email_updated.line2', ['email' => $maskedEmail])) }}

{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::mail.email_updated.line3')) }}

{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::mail.email_updated.line4', ['url' => $url, 'days' => $linkExpirationDays])) }}

{{ \Rawilk\ProfileFilament\renderMarkdown($requestDetails) }}

{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::mail.email_updated.salutation', ['app_name' => config('app.name')])) }}
</x-mail::message>
