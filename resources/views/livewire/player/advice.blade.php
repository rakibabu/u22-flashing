<div class="space-y-6">
    <x-page-header title="Coachadvies" description="Adviezen die zichtbaar zijn gemaakt door de coach." />
    <div class="space-y-3">
        @forelse ($notes as $note)
            <x-advice-card :note="$note" wire:key="player-note-{{ $note->id }}" />
        @empty
            <p class="rounded-lg border border-zinc-200 bg-white p-4 text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">Nog geen zichtbaar advies.</p>
        @endforelse
    </div>
</div>
