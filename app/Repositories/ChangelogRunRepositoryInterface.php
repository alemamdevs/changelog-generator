<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Release;

interface ChangelogRunRepositoryInterface
{
    public function createRun(array $data): Release;
}
