<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-primary-800 antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-linear-to-br from-primary-800 via-primary-700 to-primary-600 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium text-white" wire:navigate>
                    <img src="{{ asset('images/flashing/logo-white.svg') }}" alt="Flashing Heiloo" class="mb-2 h-14 w-auto">
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6 rounded-xl border border-white/10 bg-white p-6 text-primary-800 shadow-2xl shadow-black/25">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @include('partials.flux-scripts')
    </body>
</html>
