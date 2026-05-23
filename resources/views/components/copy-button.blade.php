@props([
    'text',
    'label' => 'Kopieer',
])

<span class="inline-flex" x-data>
    <flux:button
        type="button"
        x-on:click="navigator.clipboard.writeText($refs.copyText.value)"
        {{ $attributes }}
    >
        {{ $label }}
    </flux:button>

    <textarea x-ref="copyText" class="sr-only" readonly tabindex="-1">{{ $text }}</textarea>
</span>
