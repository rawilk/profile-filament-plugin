@props([
    'userHandle' => null,
])

<div {{ $attributes->class(['pf-signed-in-as border dark:border-gray-500 rounded-md py-3 px-2.5']) }}>
    <p class="text-sm text-gray-950 dark:text-white">
        {{ str(__('profile-filament::messages.sudo_challenge.signed_in_as', ['handle' => e($userHandle)]))->inlineMarkdown()->toHtmlString() }}
    </p>
</div>
