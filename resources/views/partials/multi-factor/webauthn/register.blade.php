<div
    x-on:webauthn-register-started.window.stop="show(); hasErrors = false"
    x-on:webauthn-register-stopped.window.stop="hide"
    x-on:webauthn-register-failed.window.stop="hasErrors = true"
    x-data="{
        processing: false,
        hasErrors: false,
        parentClass: '.fi-grid-col',
        init() {
            if (! browserSupportsWebAuthn) {
                const unsupportedContent = @js(Blade::render('profile-filament::livewire.partials.webauthn-unsupported'));
                const parent = this.$el.closest(this.parentClass);

                parent && parent.insertAdjacentHTML('beforebegin', unsupportedContent);

                const button = this.$el.closest('form').querySelector('.webauthn-register');
                button && button.setAttribute('disabled', true);
            }

            {{-- if we don't do this, sometimes hiding the parent element fails --}}
            this.$el.closest(this.parentClass).setAttribute('wire:ignore.self', true);

            this.hide();
        },
        hide(event = null) {
            this.processing = false;

            const hasErrors = event?.detail?.hasErrors ?? false;
            this.hasErrors = hasErrors;

            if (! hasErrors) {
                this.$nextTick(() => {
                    this.$el.closest(this.parentClass).classList.add('hidden');
                });
            }
        },
        show() {
            this.processing = true;
            this.$el.closest(this.parentClass).classList.remove('hidden');
        },
    }"
    wire:ignore
>
    <x-profile-filament::webauthn-waiting-indicator
        x-show="processing"
        style="display: none;"
        x-cloak
        wire:ignore
        :message="__('profile-filament::auth/multi-factor/webauthn/provider.messages.waiting-for-input')"
    />

    <div x-show="hasErrors">
        <template x-if="hasErrors">
            <p class="text-danger-500 text-center text-pretty" role="alert">
                {{ __('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.failed') }}
            </p>
        </template>
    </div>
</div>

@script
    <script>
        Livewire.on('webauthnRegistrationReady', async function ([{ webauthnOptions }]) {
            window.dispatchEvent(
                new CustomEvent('webauthn-register-started')
            );

            let hasErrors = false;

            const securityKey = await startRegistration({ optionsJSON: webauthnOptions })
                .catch(() => {
                    hasErrors = true;

                    window.dispatchEvent(
                        new CustomEvent('webauthn-register-failed')
                    );
                })
                .finally(() => window.dispatchEvent(
                    new CustomEvent('webauthn-register-stopped', {
                        detail: {
                            hasErrors,
                        }
                    })
                ));

            if (securityKey) {
                Livewire.find('{{ $livewireId }}').call('callMountedAction', { securityKey: JSON.stringify(securityKey) });
            }
        });
    </script>
@endscript
