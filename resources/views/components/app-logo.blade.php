@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes->class('u22-brand-link flex min-w-0 items-center rounded-xl px-2 py-2 transition hover:bg-white/10') }}>
        <img src="{{ asset('images/flashing/logo-white.svg') }}" alt="Flashing Heiloo" class="h-11 w-auto max-w-[10.5rem] shrink-0">
        <span class="sr-only">Flashing Heiloo U22 Monitoring</span>
    </a>
@else
    <a {{ $attributes->class('u22-brand-link flex min-w-0 items-center rounded-xl px-2 py-2 transition hover:bg-primary-50') }}>
        <img src="{{ asset('images/flashing/logo.svg') }}" alt="Flashing Heiloo" class="h-11 w-auto max-w-[10.5rem] shrink-0">
        <span class="sr-only">Flashing Heiloo U22 Monitoring</span>
    </a>
@endif
