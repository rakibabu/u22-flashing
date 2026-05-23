@props(['label', 'value', 'tone' => 'neutral'])

@php
    $toneClass = match ($tone) {
        'green' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100',
        'orange' => 'border-flash-orange/30 bg-flash-orange/10 text-primary-900 dark:border-flash-orange/40 dark:bg-flash-orange/15 dark:text-orange-50',
        'red' => 'border-red-200 bg-red-50 text-red-950 dark:border-red-900 dark:bg-red-950 dark:text-red-100',
        default => 'border-primary-800/10 bg-white text-primary-900 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800 dark:text-white',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border p-4 '.$toneClass]) }}>
    <p class="text-xs font-medium uppercase tracking-normal opacity-75">{{ $label }}</p>
    <p class="mt-2 font-display text-4xl font-normal leading-none">{{ $value }}</p>
</div>
