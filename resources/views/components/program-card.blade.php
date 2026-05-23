@props(['title', 'body'])

<article {{ $attributes->merge(['class' => 'rounded-lg border border-primary-800/10 border-l-4 border-l-flash-orange bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:border-l-flash-orange dark:bg-primary-800']) }}>
    <h3 class="font-semibold text-primary-900 dark:text-white">{{ $title }}</h3>
    <p class="mt-2 text-sm text-slate-700 dark:text-slate-200">{{ $body }}</p>
</article>
