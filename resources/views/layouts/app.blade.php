<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-white dark:bg-primary-900">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
