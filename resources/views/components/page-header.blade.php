@props(['title', 'description' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 border-b border-primary-800/10 pb-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        <p class="text-xs font-semibold uppercase tracking-normal text-flash-orange">Flashing Heiloo U22</p>
        <h1 class="mt-1 font-display text-3xl font-normal leading-none text-primary-900 dark:text-white">{{ $title }}</h1>
        <div class="mt-3 h-1 w-12 bg-flash-orange"></div>
        @if ($description)
            <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-300">{{ $description }}</p>
        @endif
    </div>
    @if ($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
