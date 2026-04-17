@props([
    'userHandle' => null,
])

<div {{ $attributes->class(['pf-signed-in-as border border-gray-300 dark:border-gray-500 rounded-md py-3 px-2.5 w-full']) }}>
    <p class="text-sm text-gray-950 dark:text-white">
        {{ str(__('profile-filament::auth/sudo/sudo.challenge.signed-in-as.content', ['handle' => e($userHandle)]))->inlineMarkdown()->toHtmlString() }}
    </p>
</div>
