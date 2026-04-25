<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Project $project): bool
    {
        return (int) $project->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, Project $project): bool
    {
        return (int) $project->user_id === (int) $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return (int) $project->user_id === (int) $user->id;
    }
}
