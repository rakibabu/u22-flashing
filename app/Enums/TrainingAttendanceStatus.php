<?php

namespace App\Enums;

enum TrainingAttendanceStatus: string
{
    case Unknown = 'unknown';
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Injured = 'injured';
    case Limited = 'limited';
}
