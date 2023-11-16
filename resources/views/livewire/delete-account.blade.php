<div>
    <x-profile-filament::component-section>
        <x-slot:title>
            <span class="text-danger-600 dark:text-danger-500">
                {{ __('profile-filament::pages/settings.delete_account.title') }}
            </span>
        </x-slot:title>

        <p>
            {{ __('profile-filament::pages/settings.delete_account.description') }}
        </p>

        <div class="mt-4">
            {{ $this->deleteAction }}
        </div>
    </x-profile-filament::component-section>

    <x-filament-actions::modals />
</div>
