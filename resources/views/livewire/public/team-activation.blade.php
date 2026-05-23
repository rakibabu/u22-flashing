<div class="space-y-6">
    <x-auth-header
        title="Teamactivatie"
        description="Maak je speleraccount aan voor Flashing Heiloo U22."
    />

    <div class="rounded-xl border border-primary-100 bg-primary-50/70 p-3 text-sm text-primary-900">
        Vul je naam in zoals de coach hem heeft toegevoegd. We tonen geen spelerslijst.
        @if ($expiresAtLabel)
            <span class="mt-1 block text-xs font-semibold uppercase text-primary-700">Link geldig tot {{ $expiresAtLabel }}</span>
        @endif
    </div>

    @if ($step === 1)
        <form wire:submit="checkName" class="space-y-5">
            <flux:input
                wire:model.live.debounce.400ms="name"
                label="Jouw naam"
                placeholder="Voornaam Achternaam"
                autocomplete="name"
                autofocus
            />

            <flux:button type="submit" variant="primary" class="w-full">
                Verder
            </flux:button>
        </form>
    @else
        <form wire:submit="activate" class="space-y-5">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-950">
                Naam gevonden: <strong>{{ $matchedPlayerName }}</strong>
            </div>

            <flux:input
                wire:model.live.debounce.400ms="username"
                label="Gebruikersnaam"
                autocomplete="username"
                required
            />

            <flux:input
                wire:model.live.debounce.400ms="email"
                type="email"
                label="E-mail optioneel"
                placeholder="naam@example.com"
                autocomplete="email"
            />

            <flux:input
                wire:model="password"
                type="password"
                label="Wachtwoord"
                autocomplete="new-password"
                required
                viewable
            />

            <flux:input
                wire:model="password_confirmation"
                type="password"
                label="Herhaal wachtwoord"
                autocomplete="new-password"
                required
                viewable
            />

            <div class="flex gap-3">
                <flux:button type="button" wire:click="startOver" class="flex-1">
                    Terug
                </flux:button>

                <flux:button type="submit" variant="primary" class="flex-1">
                    Account maken
                </flux:button>
            </div>
        </form>
    @endif
</div>
