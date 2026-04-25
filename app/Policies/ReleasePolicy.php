<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Release;
use App\Models\User;

final class ReleasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Release $release): bool
    {
        return (int) $release->user_id === (int) $user->id;
    }
}
