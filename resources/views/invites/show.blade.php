<x-layouts::auth :title="'Account activeren'">
    <div class="space-y-6">
        <x-auth-header title="Hoi {{ $invite->player->name }}" description="Maak je speleraccount aan voor Flashing Heiloo U22." />

        <form method="POST" action="{{ route('invite.store', $token) }}" class="space-y-5">
            @csrf
            <flux:input name="email" type="email" label="E-mail optioneel" :value="old('email')" placeholder="naam@example.com" />
            <flux:input name="username" label="Username als je geen e-mail gebruikt" :value="old('username')" placeholder="voornaam-achternaam" />
            <flux:input name="password" type="password" label="Wachtwoord" required viewable />
            <flux:input name="password_confirmation" type="password" label="Herhaal wachtwoord" required viewable />
            <flux:button type="submit" variant="primary" class="w-full">Account activeren</flux:button>
        </form>
    </div>
</x-layouts::auth>
