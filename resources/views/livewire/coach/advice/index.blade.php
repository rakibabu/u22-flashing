<div class="space-y-6">
    <x-page-header title="Adviezen" description="Beheer coachadviezen en WhatsApp-teksten." />
    <div class="space-y-3">
        @foreach ($notes as $note)
            <article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900" wire:key="coach-note-{{ $note->id }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-semibold">{{ $note->player->name }} - {{ $note->title }}</h2>
                        <p class="mt-2 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $note->body }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <flux:button size="sm" wire:click="toggleVisible({{ $note->id }})">{{ $note->visible_to_player ? 'Verberg' : 'Zichtbaar' }}</flux:button>
                        <input readonly value="{{ $note->body }}" class="w-48 rounded-md border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-800 dark:bg-zinc-950">
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>
