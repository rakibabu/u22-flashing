@props(['note'])

<article {{ $attributes->merge(['class' => 'rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="font-semibold text-primary-900 dark:text-white">{{ $note->title }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-300">{{ $note->created_at?->format('d-m-Y') }}</p>
        </div>
        <x-status-badge :status="$note->visible_to_player ? 'green' : 'orange'" />
    </div>
    <p class="mt-3 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ $note->body }}</p>
</article>
