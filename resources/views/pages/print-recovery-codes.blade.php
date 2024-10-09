@php
    /**
     * The only purpose of this view is to render a user's mfa recovery codes
     * in a print-friendly way, so no scripts are required to render the page.
     */

    abort_unless(
        \Rawilk\ProfileFilament\Facades\Mfa::userHasMfaEnabled(),
        \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
    );

    \Rawilk\ProfileFilament\Events\RecoveryCodesViewed::dispatch(filament()->auth()->user());
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}"
    @class([
        'fi min-h-screen',
        'dark' => filament()->hasDarkModeForced(),
    ])
>
    <head>
        {{ \Filament\Support\Facades\FilamentView::renderHook('panels::head.start') }}

        <meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title>
            {{ filament()->getBrandName() }}
        </title>

        {{ \Filament\Support\Facades\FilamentView::renderHook('panels::styles.before') }}

        <style>
            [x-cloak=''],
            [x-cloak='x-cloak'],
            [x-cloak='1'] {
                display: none !important;
            }

            @media (max-width: 1023px) {
                [x-cloak='-lg'] {
                    display: none !important;
                }
            }

            @media (min-width: 1024px) {
                [x-cloak='lg'] {
                    display: none !important;
                }
            }
        </style>

        {{ \Filament\Support\Facades\FilamentView::renderHook('panels::styles.after') }}

        <link rel="stylesheet"
              href="{{ \Filament\Support\Facades\FilamentAsset::getStyleHref('profile-filament-plugin', package: \Rawilk\ProfileFilament\ProfileFilamentPlugin::PLUGIN_ID) }}">

        @filamentStyles
        {{ filament()->getTheme()->getHtml() }}
        {{ filament()->getFontHtml() }}

        <style>
            :root {
                --font-family: {!! filament()->getFontFamily() !!};
                --sidebar-width: {{ filament()->getSidebarWidth() }};
                --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
            }
        </style>

        @if (! filament()->hasDarkMode())
            <script>
                localStorage.setItem('theme', 'light')
            </script>
        @elseif (filament()->hasDarkModeForced())
            <script>
                localStorage.setItem('theme', 'dark')
            </script>
        @else
            <script>
                const theme = localStorage.getItem('theme') ?? 'system'

                if (
                    theme === 'dark' ||
                    (theme === 'system' &&
                        window.matchMedia('(prefers-color-scheme: dark)')
                            .matches)
                ) {
                    document.documentElement.classList.add('dark')
                }
            </script>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook('panels::head.end') }}
    </head>

    <body
        class="bg-gray-50 font-normal text-gray-950 antialiased dark:bg-gray-950 dark:text-white"
    >
        <div class="w-1/2 mx-auto my-4 border border-gray-300 dark:border-gray-500 rounded-md">
            <div class="px-6 py-4">
                <h1 class="text-2xl font-semibold text-gray-950 dark:text-white tracking-tight">
                    {{ __('profile-filament::pages/security.mfa.recovery_codes.actions.print.print_page_title') }}
                </h1>

                <ul role="list"
                    class="list-disc list-inside text-base text-gray-950 dark:text-white mt-6"
                >
                    @foreach (filament()->auth()->user()->recoveryCodes() as $recoveryCode)
                        <li>{{ $recoveryCode }}</li>
                    @endforeach
                </ul>
            </div>

            <div
                class="mt-6 rounded-b-md border-t border-gray-300 dark:border-gray-500 bg-gray-100 dark:bg-gray-800 text-gray-950 dark:text-gray-200 px-6 py-4">
                <p class="text-sm">
                    {{ __('profile-filament::pages/security.mfa.recovery_codes.actions.print.print_page_description', ['app_name' => config('app.name')]) }}
                </p>
            </div>
        </div>
    </body>
</html>
