<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Commit;

interface CommitRepositoryInterface
{
    public function createCommit(array $data): Commit;
}
