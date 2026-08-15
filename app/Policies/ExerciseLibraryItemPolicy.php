<?php

namespace App\Policies;

use App\Models\ExerciseLibraryItem;
use App\Models\User;

class ExerciseLibraryItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessCoachArea();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ExerciseLibraryItem $exerciseLibraryItem): bool
    {
        return $user->canAccessCoachArea();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isCoach();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExerciseLibraryItem $exerciseLibraryItem): bool
    {
        return $user->isCoach();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExerciseLibraryItem $exerciseLibraryItem): bool
    {
        return $user->isCoach();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExerciseLibraryItem $exerciseLibraryItem): bool
    {
        return $user->isCoach();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExerciseLibraryItem $exerciseLibraryItem): bool
    {
        return false;
    }
}
