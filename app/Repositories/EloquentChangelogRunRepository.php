<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Release;
use App\Repositories\ChangelogRunRepositoryInterface;

final class EloquentChangelogRunRepository implements ChangelogRunRepositoryInterface
{
    public function createRun(array $data): Release
    {
        return Release::create($data);
    }
}
