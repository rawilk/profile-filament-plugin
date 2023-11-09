<div class="border dark:border-gray-500 rounded-md py-3 px-2.5">
    <p class="text-sm text-gray-950 dark:text-white">
        {{ new \Illuminate\Support\HtmlString(
            \Illuminate\Support\Str::inlineMarkdown(__('profile-filament::messages.sudo_challenge.signed_in_as', ['handle' => filament()->auth()->user()->email]))
        ) }}
    </p>
</div>
