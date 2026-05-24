<div class="space-y-6">
    <x-page-header title="Adviezen" description="Beheer coachadviezen en WhatsApp-teksten." />
    <div class="space-y-3">
        @foreach ($notes as $note)
            <article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900" wire:key="coach-note-{{ $note->id }}">
                @if ($editingNoteId === $note->id)
                    <form wire:submit="update" class="space-y-3">
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $note->player->name }}</p>

                        <flux:input wire:model="editingTitle" label="Titel" />
                        <flux:error name="editingTitle" />

                        <flux:textarea wire:model="editingBody" label="Advies" rows="5" />
                        <flux:error name="editingBody" />

                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="editingVisibleToPlayer" class="rounded border-zinc-300">
                            Zichtbaar voor speler
                        </label>

                        <div class="flex flex-wrap gap-2">
                            <flux:button type="submit" size="sm" variant="primary" icon="check">Opslaan</flux:button>
                            <flux:button type="button" size="sm" wire:click="cancelEdit">Annuleer</flux:button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="font-semibold">{{ $note->player->name }} - {{ $note->title }}</h2>
                            <p class="mt-2 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $note->body }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $note->id }})">Bewerk</flux:button>
                            <flux:button size="sm" wire:click="toggleVisible({{ $note->id }})">{{ $note->visible_to_player ? 'Verberg' : 'Zichtbaar' }}</flux:button>
                            <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $note->id }})" wire:confirm="Weet je zeker dat je dit advies wilt verwijderen?">Verwijder</flux:button>
                            <input readonly value="{{ $note->body }}" class="w-48 rounded-md border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-800 dark:bg-zinc-950">
                        </div>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</div>
