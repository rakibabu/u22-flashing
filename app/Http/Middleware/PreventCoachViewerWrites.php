<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PreventCoachViewerWrites
{
    /**
     * @var array<string, array{methods: array<int, string>, properties: array<int, string>}>
     */
    private const ALLOWED_LIVEWIRE_INTERACTIONS = [
        'coach.dashboard' => [
            'methods' => ['$commit', 'previousWeek', 'nextWeek', 'currentWeek'],
            'properties' => ['search', 'program', 'status', 'week'],
        ],
        'coach.analysis-export' => [
            'methods' => ['$commit', 'previousWeek', 'nextWeek', 'currentWeek'],
            'properties' => ['week'],
        ],
        'coach.players' => [
            'methods' => ['$commit'],
            'properties' => ['search'],
        ],
        'coach.players.show' => [
            'methods' => ['$commit', 'previousAdviceWeek', 'nextAdviceWeek', 'currentAdviceWeek'],
            'properties' => ['adviceWeek'],
        ],
        'coach.players.checkin-preview' => [
            'methods' => ['$commit', 'nextStep', 'previousStep', 'goToStep'],
            'properties' => ['form', 'step'],
        ],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isCoachViewer()) {
            return $next($request);
        }

        if ($request->isMethodSafe() || $request->routeIs('logout')) {
            return $next($request);
        }

        if ($request->routeIs('*livewire.update') && $this->isAllowedLivewireUpdate($request)) {
            return $next($request);
        }

        abort(403, 'Dit demo-account heeft alleen-lezen toegang.');
    }

    private function isAllowedLivewireUpdate(Request $request): bool
    {
        $components = $request->input('components');

        if (! is_array($components) || $components === []) {
            return false;
        }

        foreach ($components as $component) {
            if (! is_array($component) || ! $this->isAllowedComponentUpdate($component)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $component
     */
    private function isAllowedComponentUpdate(array $component): bool
    {
        $snapshot = json_decode((string) ($component['snapshot'] ?? ''), true);
        $componentName = is_array($snapshot) ? data_get($snapshot, 'memo.name') : null;
        $allowedInteractions = is_string($componentName)
            ? self::ALLOWED_LIVEWIRE_INTERACTIONS[$componentName] ?? null
            : null;

        if ($allowedInteractions === null) {
            return false;
        }

        $updates = $component['updates'] ?? [];

        if (! is_array($updates)) {
            return false;
        }

        foreach (array_keys($updates) as $property) {
            if (! is_string($property) || ! in_array(Str::before($property, '.'), $allowedInteractions['properties'], true)) {
                return false;
            }
        }

        $calls = $component['calls'] ?? [];

        if (! is_array($calls)) {
            return false;
        }

        foreach ($calls as $call) {
            $method = is_array($call) ? $call['method'] ?? null : null;

            if (! is_string($method) || ! in_array($method, $allowedInteractions['methods'], true)) {
                return false;
            }
        }

        return true;
    }
}
