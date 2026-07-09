<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-white dark:bg-primary-900">
        @if (auth()->user()->isCoachViewer())
            <flux:callout
                variant="warning"
                icon="eye"
                heading="Demomodus"
                text="Je gebruikt een demo-account met alleen-lezen toegang. Alle beheerinformatie is zichtbaar, maar wijzigingen zijn uitgeschakeld."
                class="mb-6"
            />
        @endif

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
