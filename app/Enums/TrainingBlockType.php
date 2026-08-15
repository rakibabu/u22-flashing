<?php

namespace App\Enums;

enum TrainingBlockType: string
{
    case Exercise = 'exercise';
    case Text = 'text';
    case Break = 'break';
    case Briefing = 'briefing';
}
