<div class="space-y-6">
    <x-page-header title="Spelers" description="Beheer programma's en invite-links.">
        <x-slot:actions>
            <flux:button :href="route('coach.players.create')" variant="primary" wire:navigate>Speler toevoegen</flux:button>
        </x-slot:actions>
    </x-page-header>

    <section class="rounded-xl border border-primary-100 bg-white p-4 shadow-sm shadow-gray-100/50">
        <div>
            <p class="text-xs font-semibold uppercase text-primary-700">Trainingsprogramma PDF's</p>
            <h2 class="mt-1 font-display text-3xl leading-none text-primary-900">Per trainingstype</h2>
            <p class="mt-1 max-w-2xl text-sm text-zinc-600">
                Upload hier de PDF voor conditie, bulk/kracht of onderhoud. Spelers zien automatisch de PDF van hun eigen trainingstype.
            </p>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            @foreach ($programTemplates as $template)
                <form
                    method="POST"
                    action="{{ route('coach.program-templates.pdf.store', $template) }}"
                    enctype="multipart/form-data"
                    class="rounded-lg border border-primary-100 bg-primary-50/50 p-3"
                    wire:key="program-template-pdf-{{ $template->id }}"
                >
                    @csrf

                    <div class="flex min-h-16 flex-col justify-between gap-1">
                        <h3 class="font-semibold text-primary-900">{{ $template->name }}</h3>
                        <p class="text-xs text-zinc-600">{{ $template->training_program_pdf_path ? 'PDF ingesteld' : 'Nog geen PDF' }}</p>
                    </div>

                    <div class="mt-3 space-y-3">
                        <label class="block text-sm font-medium text-primary-900" for="training-program-pdf-{{ $template->id }}">
                            PDF uploaden
                        </label>
                        <input
                            id="training-program-pdf-{{ $template->id }}"
                            name="training_program_pdf"
                            type="file"
                            accept="application/pdf"
                            class="block w-full rounded-md border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 file:mr-3 file:rounded-md file:border-0 file:bg-primary-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-primary-900"
                        >

                        @error('training_program_pdf')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        @if (session('saved_program_template_id') === $template->id)
                            <p class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-950">PDF opgeslagen.</p>
                        @endif

                        <div class="flex flex-wrap gap-2">
                            <flux:button type="submit" size="sm" variant="primary">Opslaan</flux:button>
                            @if ($template->training_program_pdf_path)
                                <flux:button size="sm" :href="route('coach.program-templates.pdf', $template)" target="_blank">Bekijk PDF</flux:button>
                            @endif
                        </div>
                    </div>
                </form>
            @endforeach
        </div>
    </section>

    <section class="rounded-xl border border-primary-100 bg-white p-4 shadow-sm shadow-gray-100/50">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase text-primary-700">Teamactivatie-link</p>
                <h2 class="mt-1 font-display text-3xl leading-none text-primary-900">1 WhatsApp-link voor spelers</h2>
                <p class="mt-1 max-w-2xl text-sm text-zinc-600">
                    Spelers vullen hun naam in. Alleen bestaande spelers zonder account kunnen claimen.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button wire:click="generateTeamInvite" variant="primary">Nieuwe teamlink</flux:button>

                @if ($teamInviteLink)
                    <x-copy-button :text="$teamInviteLink" label="Kopieer link" />
                @endif

                @if ($latestTeamInvite?->usable())
                    <flux:button wire:click="revokeTeamInvite">Intrekken</flux:button>
                @endif
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-primary-100 bg-primary-50/50 px-3 py-2">
                <span class="block text-xs font-semibold uppercase text-primary-700">Status</span>
                <strong class="mt-1 block text-primary-900">{{ $latestTeamInvite?->statusLabel() ?? 'Nog geen link' }}</strong>
            </div>

            <div class="rounded-lg border border-primary-100 bg-primary-50/50 px-3 py-2">
                <span class="block text-xs font-semibold uppercase text-primary-700">Geldig tot</span>
                <strong class="mt-1 block text-primary-900">{{ $latestTeamInvite?->expires_at?->format('d-m-Y H:i') ?? 'n.v.t.' }}</strong>
            </div>

            <div class="rounded-lg border border-primary-100 bg-primary-50/50 px-3 py-2">
                <span class="block text-xs font-semibold uppercase text-primary-700">Gebruik</span>
                <strong class="mt-1 block text-primary-900">{{ $latestTeamInvite?->last_used_at ? 'Laatst gebruikt '.$latestTeamInvite->last_used_at->format('d-m-Y H:i') : 'Nog niet gebruikt' }}</strong>
            </div>
        </div>

        @if ($teamInviteLink)
            <input readonly value="{{ $teamInviteLink }}" class="mt-4 w-full rounded-md border border-flash-orange/30 bg-flash-orange/10 px-3 py-2 text-sm text-primary-900">
        @elseif ($latestTeamInvite?->usable())
            <p class="mt-4 rounded-lg border border-flash-orange/20 bg-flash-orange/10 px-3 py-2 text-sm text-primary-900">
                Er is een actieve teamlink. Maak een nieuwe teamlink als je de WhatsApp-link opnieuw wilt kopiëren.
            </p>
        @endif
    </section>

    <flux:input wire:model.live="search" placeholder="Zoek speler" class="max-w-md" />

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($players as $player)
            <article wire:key="player-{{ $player->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-zinc-950 dark:text-white">{{ $player->name }}</h2>
                        <p class="text-sm text-zinc-500">{{ $player->programName() }}</p>
                    </div>
                    <x-status-badge :status="$player->user_id ? 'green' : 'orange'" />
                </div>
                @if (isset($inviteLinks[$player->id]))
                    <input readonly value="{{ $inviteLinks[$player->id] }}" class="mt-3 w-full rounded-md border border-orange-200 bg-orange-50 px-3 py-2 text-sm text-orange-950">
                @endif
                <div class="mt-4 flex flex-wrap gap-2">
                    <flux:button size="sm" :href="route('coach.players.show', $player)" wire:navigate>Bekijk</flux:button>
                    <flux:button size="sm" :href="route('coach.players.checkin-preview', $player)" wire:navigate>Weekcheck scherm</flux:button>
                    <flux:button size="sm" wire:click="regenerateInvite({{ $player->id }})">Nieuwe invite</flux:button>
                </div>
            </article>
        @endforeach
    </div>
</div>
