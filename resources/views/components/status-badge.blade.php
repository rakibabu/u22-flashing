@props(['status'])

@php
    $classes = match ($status) {
        'green' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        'orange' => 'bg-flash-orange/15 text-primary-900 dark:bg-flash-orange/20 dark:text-orange-100',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
        default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
    };
    $label = match ($status) {
        'green' => 'Groen',
        'orange' => 'Oranje',
        'red' => 'Rood',
        default => $status,
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium '.$classes]) }}>{{ $label }}</span>
