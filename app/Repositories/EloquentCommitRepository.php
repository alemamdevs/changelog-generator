<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Commit;
use App\Repositories\CommitRepositoryInterface;

final class EloquentCommitRepository implements CommitRepositoryInterface
{
    public function createCommit(array $data): Commit
    {
        return Commit::create($data);
    }
}
