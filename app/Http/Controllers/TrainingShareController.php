<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use Illuminate\Contracts\View\View;

class TrainingShareController extends Controller
{
    public function __invoke(TrainingSession $training): View
    {
        $training->load('blocks');
        $filledMinutes = $training->blocks->sum('planned_duration_minutes');

        return view('training-share', [
            'training' => $training,
            'filledMinutes' => $filledMinutes,
            'shareDescription' => "Bekijk de training '{$training->title}' van Flashing Heiloo U22: {$training->blocks->count()} blokken, {$filledMinutes} min.",
        ]);
    }
}
