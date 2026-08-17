<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Evaluatie · {{ $run->trainingSession->title }} | Flashing Heiloo U22</title>
        <meta name="description" content="{{ $shareDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="nl_NL">
        <meta property="og:site_name" content="Flashing Heiloo U22">
        <meta property="og:title" content="Evaluatie · {{ $run->trainingSession->title }} | Flashing Heiloo U22">
        <meta property="og:description" content="{{ $shareDescription }}">
        <meta property="og:image" content="{{ asset('images/flashing/u22-training-share-preview.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="image/png">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Evaluatie · {{ $run->trainingSession->title }} | Flashing Heiloo U22">
        <meta name="twitter:description" content="{{ $shareDescription }}">
        <meta name="twitter:image" content="{{ asset('images/flashing/u22-training-share-preview.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-primary-50 text-primary-900 dark:bg-primary-900 dark:text-white">
        <main class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">
            <header class="rounded-2xl bg-primary-800 p-6 text-white sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-flash-orange">Flashing Heiloo U22 · Evaluatie</p>
                <h1 class="mt-2 font-display text-4xl leading-none sm:text-5xl">{{ $run->trainingSession->title }}</h1>
                <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-sm text-white/75">
                    <span>{{ $run->started_at->timezone('Europe/Amsterdam')->format('D d M, H:i') }}</span>
                    <span>{{ round($run->elapsedSeconds() / 60) }} min uitgevoerd</span>
                </div>
            </header>

            <section class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-primary-100 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs font-semibold uppercase text-flash-orange">Werkelijke tijd</p><p class="mt-1 font-display text-3xl">{{ round($run->elapsedSeconds() / 60) }} min</p></div>
                <div class="rounded-xl border border-primary-100 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs font-semibold uppercase text-flash-orange">Uitgevoerd</p><p class="mt-1 font-display text-3xl">{{ $completedBlocks }}</p></div>
                <div class="rounded-xl border border-primary-100 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs font-semibold uppercase text-flash-orange">Overgeslagen</p><p class="mt-1 font-display text-3xl">{{ $skippedBlocks }}</p></div>
            </section>

            <section class="rounded-xl border border-primary-100 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase text-flash-orange">Coach-evaluatie</p>
                <div class="mt-4 space-y-5">
                    @if($run->general_notes)<div><h2 class="font-semibold">Algemene notitie</h2><p class="mt-1 whitespace-pre-line">{{ $run->general_notes }}</p></div>@endif
                    @if($run->what_worked)<div><h2 class="font-semibold">Wat werkte goed?</h2><p class="mt-1 whitespace-pre-line">{{ $run->what_worked }}</p></div>@endif
                    @if($run->what_to_change)<div><h2 class="font-semibold">Wat volgende keer anders?</h2><p class="mt-1 whitespace-pre-line">{{ $run->what_to_change }}</p></div>@endif
                    @if($run->next_action)<div><h2 class="font-semibold">Belangrijkste vervolgactie</h2><p class="mt-1 whitespace-pre-line">{{ $run->next_action }}</p></div>@endif
                    @if(! $run->general_notes && ! $run->what_worked && ! $run->what_to_change && ! $run->next_action)<p class="text-zinc-600 dark:text-zinc-300">De coach heeft nog geen notities toegevoegd.</p>@endif
                </div>
            </section>

            <section class="rounded-xl border border-primary-100 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase text-flash-orange">Jouw blik als trainer</p>
                <h2 class="mt-1 font-display text-3xl leading-none">Deel je feedback</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-300">Wat viel je op, wat werkte goed en wat zou je de volgende keer anders doen?</p>
                @if(session('training-review-feedback-saved'))<p class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">{{ session('training-review-feedback-saved') }}</p>@endif
                <form method="POST" action="{{ $feedbackUrl }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="hidden" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
                    <div><label for="author_name" class="text-sm font-semibold">Jouw naam</label><input id="author_name" name="author_name" value="{{ old('author_name') }}" required maxlength="100" class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-primary-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">@error('author_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="feedback" class="text-sm font-semibold">Feedback</label><textarea id="feedback" name="feedback" required maxlength="5000" rows="5" class="mt-1 w-full rounded-lg border border-primary-100 bg-white px-3 py-2 text-primary-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">{{ old('feedback') }}</textarea>@error('feedback')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <button type="submit" class="inline-flex rounded-lg bg-flash-orange px-4 py-2 font-medium text-primary-900 transition hover:bg-flash-orange/85">Feedback versturen</button>
                </form>
            </section>

            <section class="rounded-xl border border-primary-800 bg-primary-800 p-6 text-white sm:p-8"><p class="text-sm font-semibold uppercase tracking-wide text-flash-orange">Ook trainingen organiseren?</p><div class="mt-3 h-1 w-12 bg-flash-orange"></div><h2 class="mt-4 font-display text-3xl leading-none sm:text-4xl">Werk met U22 Monitoring.</h2><p class="mt-3 max-w-xl text-white/75">Plan trainingen, deel oefeningen en evalueer samen met je coaches.</p><a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-flash-orange px-4 py-2 font-medium text-primary-900 transition hover:bg-flash-orange/85">Bekijk U22 Monitoring</a></section>
            <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">Deze deelpagina is tijdelijk beschikbaar.</p>
        </main>
    </body>
</html>
