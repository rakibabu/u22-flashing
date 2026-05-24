<x-mail::message>
# Nieuw coachadvies

Hoi {{ $coachNote->player->name }},

{{ $coachNote->coach?->name ?? 'Je coach' }} heeft nieuw advies voor je klaargezet.

<x-mail::panel>
{{ $coachNote->body }}
</x-mail::panel>

<x-mail::button :url="$adviceUrl">
Bekijk advies
</x-mail::button>

Succes deze week,<br>
{{ config('app.name') }}
</x-mail::message>
