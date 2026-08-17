<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $training->title }} · Flashing Heiloo U22</title>
        <meta name="description" content="{{ $shareDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="nl_NL">
        <meta property="og:site_name" content="Flashing Heiloo U22">
        <meta property="og:title" content="{{ $training->title }} · Training | Flashing Heiloo U22">
        <meta property="og:description" content="{{ $shareDescription }}">
        <meta property="og:image" content="{{ asset('images/flashing/u22-training-share-preview.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="image/png">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $training->title }} · Training | Flashing Heiloo U22">
        <meta name="twitter:description" content="{{ $shareDescription }}">
        <meta name="twitter:image" content="{{ asset('images/flashing/u22-training-share-preview.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-primary-50 text-primary-900 dark:bg-primary-900 dark:text-white">
        <main class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">
            <header class="rounded-2xl bg-primary-800 p-6 text-white sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-flash-orange">Flashing Heiloo U22 · Training</p>
                <h1 class="mt-2 font-display text-4xl leading-none sm:text-5xl">{{ $training->title }}</h1>
                <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-sm text-white/75">
                    <span>{{ $training->scheduled_at?->timezone('Europe/Amsterdam')->format('D d M, H:i') ?? 'Datum volgt' }}</span>
                    <span>{{ $training->planned_duration_minutes }} min</span>
                    @if($training->theme)<span>{{ $training->theme }}</span>@endif
                </div>
            </header>

            @if($training->goals)
                <section class="rounded-xl border border-primary-100 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-semibold uppercase text-flash-orange">Trainingsdoelen</p>
                    <p class="mt-2 whitespace-pre-line text-lg">{{ $training->goals }}</p>
                </section>
            @endif

            <section class="space-y-3">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase text-flash-orange">Programma</p>
                        <h2 class="mt-1 font-display text-3xl leading-none">{{ $training->blocks->count() }} blokken</h2>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $filledMinutes }} min ingevuld</p>
                </div>

                @forelse($training->blocks as $block)
                    <article class="rounded-xl border border-primary-100 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase text-flash-orange">Blok {{ $block->position }} · {{ $block->assigned_coach->value }}</p>
                                <h3 class="mt-1 text-xl font-semibold">{{ $block->title }}</h3>
                            </div>
                            <span class="shrink-0 rounded-full bg-primary-50 px-3 py-1 text-sm font-medium dark:bg-primary-800">{{ $block->planned_duration_minutes }} min</span>
                        </div>

                        @if($block->exercise_snapshot)
                            @if($block->exercise_snapshot['objective'] ?? null)<p class="mt-4"><span class="font-semibold">Doel:</span> {{ $block->exercise_snapshot['objective'] }}</p>@endif
                            @if($block->exercise_snapshot['organization'] ?? null)<p class="mt-2 whitespace-pre-line"><span class="font-semibold">Organisatie:</span> {{ $block->exercise_snapshot['organization'] }}</p>@endif
                            @if($block->exercise_snapshot['execution'] ?? null)<p class="mt-2 whitespace-pre-line"><span class="font-semibold">Uitvoering:</span> {{ $block->exercise_snapshot['execution'] }}</p>@endif
                            @if($block->exercise_snapshot['coaching_points'] ?? null)<p class="mt-2"><span class="font-semibold">Coaching points:</span> {{ implode(' · ', $block->exercise_snapshot['coaching_points']) }}</p>@endif
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-primary-800/20 p-5 text-zinc-600 dark:text-zinc-300">Deze training heeft nog geen blokken.</p>
                @endforelse
            </section>

            <section class="rounded-xl border border-primary-800 bg-primary-800 p-6 text-white sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-flash-orange">Ook trainingen organiseren?</p>
                <div class="mt-3 h-1 w-12 bg-flash-orange"></div>
                <h2 class="mt-4 font-display text-3xl leading-none sm:text-4xl">Werk met U22 Monitoring.</h2>
                <p class="mt-3 max-w-xl text-white/75">Plan trainingen, deel oefeningen en evalueer samen met je coaches.</p>
                <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-flash-orange px-4 py-2 font-medium text-primary-900 transition hover:bg-flash-orange/85">Bekijk U22 Monitoring</a>
            </section>

            <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">Deze deelpagina is tijdelijk beschikbaar.</p>
        </main>
    </body>
</html>
