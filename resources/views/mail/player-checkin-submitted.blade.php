<x-mail::message>
# Nieuwe weekcheck

Hoi {{ $coach->name }},

{{ $weeklyCheckin->player->name }} heeft de weekcheck voor de week van {{ $weeklyCheckin->week_start_date->format('d-m-Y') }} ingevuld.

<x-mail::panel>
@foreach ($summaryRows as $label => $value)
**{{ $label }}:** {{ $value }}

@endforeach
</x-mail::panel>

@if ($nutritionRows !== [])
<x-mail::panel>
@foreach ($nutritionRows as $label => $value)
**{{ $label }}:** {{ $value }}

@endforeach
</x-mail::panel>
@endif

<x-mail::button :url="$checkinUrl">
Bekijk check-in
</x-mail::button>

Je kunt ook direct naar het [spelerprofiel]({{ $playerUrl }}).

Succes,<br>
{{ config('app.name') }}
</x-mail::message>
