<div class="space-y-6">
    <x-page-header title="Analyse export" description="Kopieer deze samenvatting naar ChatGPT voor extra analyse.">
        <x-slot:actions>
            <flux:button :href="route('coach.analysis-export.csv')">CSV downloaden</flux:button>
        </x-slot:actions>
    </x-page-header>
    <textarea readonly class="min-h-[520px] w-full rounded-lg border border-zinc-200 bg-white p-4 font-mono text-sm text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100">{{ $markdown }}</textarea>
</div>
