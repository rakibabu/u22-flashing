@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('images/flashing/logo-email-white.png') }}" class="logo" alt="{{ trim(strip_tags($slot)) ?: config('app.name') }}">
</a>
</td>
</tr>
