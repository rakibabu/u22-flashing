@php
    app('livewire')->forceAssetInjection();

    $fluxScript = config('app.debug') ? 'flux/flux.js' : 'flux/flux.min.js';
    $fluxScriptPath = public_path($fluxScript);
    $fluxVersion = file_exists($fluxScriptPath) ? filemtime($fluxScriptPath) : 'dev';
@endphp

<script src="{{ asset($fluxScript) }}?id={{ $fluxVersion }}" data-navigate-once></script>
