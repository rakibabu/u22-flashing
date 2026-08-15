<?php

namespace App\Enums;

enum TrainingRunStatus: string
{
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
}
