<?php

namespace App\Enums;

enum ExerciseScope: string
{
    case Individual = 'individual';
    case Team = 'team';
    case Both = 'both';
}
