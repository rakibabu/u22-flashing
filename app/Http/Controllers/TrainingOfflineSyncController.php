<?php

namespace App\Http\Controllers;

use App\Enums\TrainingBlockRunStatus;
use App\Models\TrainingRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrainingOfflineSyncController extends Controller
{
    public function __invoke(Request $request, TrainingRun $trainingRun): JsonResponse
    {
        abort_unless($request->user()?->can('update', $trainingRun), 403);
        $data = $request->validate(['events' => ['required', 'array', 'max:100'], 'events.*.uuid' => ['required', 'uuid'], 'events.*.sequence' => ['required', 'integer', 'min:1'], 'events.*.type' => ['required', Rule::in(['note', 'add_time', 'skip', 'complete'])], 'events.*.block_id' => ['required', 'integer', Rule::exists('training_blocks', 'id')], 'events.*.payload' => ['nullable', 'array']]);
        $processed = DB::transaction(function () use ($data, $request, $trainingRun): array {
            $events = collect($data['events'])->sortBy('sequence');
            foreach ($events as $event) {
                $record = DB::table('training_offline_events')->where('uuid', $event['uuid'])->first();
                if ($record) {
                    continue;
                } $blockRun = $trainingRun->blockRuns()->firstOrCreate(['training_block_id' => $event['block_id']]);
                abort_unless($blockRun->trainingBlock->training_session_id === $trainingRun->training_session_id, 422);
                if ($event['type'] === 'note') {
                    $blockRun->update(['notes' => $event['payload']['notes'] ?? null]);
                } if ($event['type'] === 'add_time') {
                    $blockRun->increment('added_duration_seconds', min(3600, max(0, (int) ($event['payload']['seconds'] ?? 0))));
                } if (in_array($event['type'], ['skip', 'complete'], true)) {
                    $blockRun->update(['status' => $event['type'] === 'skip' ? TrainingBlockRunStatus::Skipped : TrainingBlockRunStatus::Completed, 'ended_at' => now()]);
                } DB::table('training_offline_events')->insert(['uuid' => $event['uuid'], 'training_run_id' => $trainingRun->id, 'user_id' => $request->user()->id, 'sequence' => $event['sequence'], 'type' => $event['type'], 'payload' => json_encode($event['payload'] ?? []), 'processed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }

            return $events->pluck('uuid')->all();
        });

        return response()->json(['processed' => $processed]);
    }
}
