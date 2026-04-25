@props([
    'promptText' => null,
    'failedText' => null,
    'livewireId',
])

<div
    x-on:webauthn-auth-started.window.stop="processing = true; hasErrors = false"
    x-on:webauthn-auth-stopped.window.stop="processing = false"
    x-on:webauthn-auth-failed.window.stop="onFail"
    x-data="{
        processing: false,
        hasErrors: false,
        errorMessage: undefined,

        onFail(event) {
            this.hasErrors = true;

            this.errorMessage = event?.detail?.message ?? {{ Js::from($failedText) }};
        },
    }"
    wire:ignore
>
    @if ($promptText)
        <div class="text-center text-pretty" x-show="(! processing) && (! hasErrors)">{{ $promptText }}</div>
    @endif

    <x-profile-filament::webauthn-waiting-indicator
        x-show="processing"
        style="display: none;"
        x-cloak
        :message="__('profile-filament::auth/multi-factor/webauthn/provider.messages.waiting-for-input')"
    />

    @if ($failedText)
        <div x-show="hasErrors && ! processing">
            <template x-if="hasErrors">
                <p class="text-danger-500 text-center text-pretty" role="alert" x-text="errorMessage">
                </p>
            </template>
        </div>
    @endif
</div>

@script
<script>
    const isArray = obj => Array.isArray(obj);
    const isObjectish = obj => typeof obj === 'object' && obj !== null;
    const isObject = obj => isObjectish(obj) && ! isArray(obj);
    const objectHasKey = (obj, key) => key in obj;

    Livewire.on('webauthnAuthenticationReady', async function ([{ webauthnOptions }]) {
        window.dispatchEvent(
            new CustomEvent('webauthn-auth-started')
        );

        const authenticationResponse = await startAuthentication({ optionsJSON: webauthnOptions })
            .catch(() => window.dispatchEvent(
                new CustomEvent('webauthn-auth-failed')
            ))
            .finally(() => window.dispatchEvent(
                new CustomEvent('webauthn-auth-stopped')
            ));

        if (! isObject(authenticationResponse)) {
            return;
        }

        if (! objectHasKey(authenticationResponse, 'id')) {
            return;
        }

        Livewire.find('{{ $livewireId }}').call(
            'mountAction',
            'authenticateWebauthn',
            {
                authenticationResponse: JSON.stringify(authenticationResponse),
            },
            @js([
                'schemaComponent' => 'form.webauthn',
            ])
        );
    });

    Livewire.on('webauthnAuthenticationFailed', function ([{ message }]) {
        window.dispatchEvent(
            new CustomEvent('webauthn-auth-failed', {
                detail: {
                    message,
                }
            })
        )
    });
</script>
@endscript
