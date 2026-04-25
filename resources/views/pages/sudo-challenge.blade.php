@php
    use Filament\Schemas\Components\RenderHook;
    use Rawilk\ProfileFilament\Enums\RenderHook as PluginRenderHook;
@endphp

<x-filament-panels::page.simple>
    {{ RenderHook::make(PluginRenderHook::SudoChallengeBefore->value) }}

    <x-profile-filament::sudo.signed-in-as
        :user-handle="filament()->auth()->user()->email"
    />

    <x-profile-filament::sudo.form-content
        :heading="$this->currentProviderInstance?->heading($this->user)"
        :icon="$this->currentProviderInstance?->icon()"
        :current-provider="$currentProvider"
    >
        {{ $this->content }}

        @unless (empty($this->alternateOptions->getComponents()))
            <x-slot:alternatives>
                {{ $this->alternateOptions }}
            </x-slot:alternatives>
        @endunless
    </x-profile-filament::sudo.form-content>

    <div class="pf-sudo-tip text-xs">
        {{ str(__('profile-filament::auth/sudo/sudo.challenge.tip'))->inlineMarkdown()->toHtmlString() }}
    </div>

    {{ RenderHook::make(PluginRenderHook::SudoChallengeAfter->value) }}
</x-filament-panels::page.simple>
